<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\pharma;
use App\Models\branch;
use App\Models\medicines;
use App\Models\order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get admin dashboard statistics
     */
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_pharmas' => pharma::count(),
            'total_branches' => branch::count(),
            'total_medicines' => medicines::count(),
            'total_orders' => order::count(),
            
            'users_by_role' => User::select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->get()
                ->pluck('count', 'role'),
            
            'recent_users' => User::latest()->take(10)->get(),
            'recent_orders' => order::with('user', 'branch')
                ->latest()
                ->take(10)
                ->get(),
            
            'orders_by_status' => order::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status'),
            
            'revenue' => [
                'total' => order::where('status', 'completed')->sum('total_price'),
                'pending' => order::where('status', 'pending')->sum('total_price'),
                'today' => order::where('status', 'completed')
                    ->whereDate('created_at', today())
                    ->sum('total_price'),
                'this_month' => order::where('status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('total_price'),
            ],
            
            'top_selling_medicines' => medicines::select('medicines.*', DB::raw('SUM(order_items.quantity) as total_sold'))
                ->join('order_items', 'medicines.id', '=', 'order_items.medicine_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.status', 'completed')
                ->groupBy('medicines.id')
                ->orderBy('total_sold', 'desc')
                ->take(10)
                ->get(),
            
            'low_stock_medicines' => medicines::where('quantity', '<=', 10)
                ->with('branch', 'pharma')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Get all users (admin only)
     */
    public function users(Request $request)
    {
        $query = User::query();

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->with('pharmacy')->paginate(15);
        return response()->json($users);
    }

    /**
     * Update user role
     */
    public function updateUserRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:admin,pharma,user'
        ]);

        $user->update(['role' => $request->role]);

        return response()->json([
            'message' => 'User role updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Delete user (soft delete recommended)
     */
    public function deleteUser(User $user)
    {
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Cannot delete admin users'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Get system-wide analytics
     */
    public function analytics(Request $request)
    {
        $days = $request->get('days', 30);

        $analytics = [
            'orders_trend' => order::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(total_price) as revenue')
                )
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            'user_growth' => User::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            'pharma_performance' => pharma::select(
                    'pharmas.id',
                    'pharmas.name',
                    DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                    DB::raw('SUM(orders.total_price) as total_revenue')
                )
                ->join('branches', 'pharmas.id', '=', 'branches.pharma_id')
                ->join('orders', 'branches.id', '=', 'orders.branch_id')
                ->where('orders.status', 'completed')
                ->groupBy('pharmas.id', 'pharmas.name')
                ->orderBy('total_revenue', 'desc')
                ->get(),
        ];

        return response()->json($analytics);
    }
}
