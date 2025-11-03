<?php

namespace App\Http\Controllers\Pharma;

use App\Http\Controllers\Controller;
use App\Models\pharma;
use App\Models\branch;
use App\Models\medicines;
use App\Models\order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PharmaDashboardController extends Controller
{
    /**
     * Get pharmacy owner dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $pharma = $user->pharmacy;

        if (!$pharma) {
            return response()->json([
                'message' => 'No pharmacy associated with this account'
            ], 404);
        }

        $stats = [
            'pharmacy' => $pharma->load('branches'),
            
            'overview' => [
                'total_branches' => $pharma->branches->count(),
                'total_medicines' => medicines::whereIn('branch_id', $pharma->branches->pluck('id'))->count(),
                'total_orders' => order::whereIn('branch_id', $pharma->branches->pluck('id'))->count(),
                'pending_orders' => order::whereIn('branch_id', $pharma->branches->pluck('id'))
                    ->where('status', 'pending')
                    ->count(),
            ],

            'revenue' => [
                'total' => order::whereIn('branch_id', $pharma->branches->pluck('id'))
                    ->where('status', 'completed')
                    ->sum('total_price'),
                'today' => order::whereIn('branch_id', $pharma->branches->pluck('id'))
                    ->where('status', 'completed')
                    ->whereDate('created_at', today())
                    ->sum('total_price'),
                'this_month' => order::whereIn('branch_id', $pharma->branches->pluck('id'))
                    ->where('status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('total_price'),
            ],

            'recent_orders' => order::whereIn('branch_id', $pharma->branches->pluck('id'))
                ->with('user', 'branch', 'orderItems.medicine')
                ->latest()
                ->take(10)
                ->get(),

            'low_stock_alerts' => medicines::whereIn('branch_id', $pharma->branches->pluck('id'))
                ->where('quantity', '<=', 10)
                ->with('branch')
                ->get(),

            'top_selling' => medicines::select('medicines.*', DB::raw('SUM(order_items.quantity) as total_sold'))
                ->join('order_items', 'medicines.id', '=', 'order_items.medicine_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereIn('medicines.branch_id', $pharma->branches->pluck('id'))
                ->where('orders.status', 'completed')
                ->groupBy('medicines.id')
                ->orderBy('total_sold', 'desc')
                ->take(5)
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Get pharmacy branches
     */
    public function branches(Request $request)
    {
        $pharma = $request->user()->pharmacy;

        $branches = $pharma->branches()
            ->withCount(['medicines', 'orders'])
            ->with('medicines')
            ->get();

        return response()->json($branches);
    }

    /**
     * Get pharmacy medicines across all branches
     */
    public function medicines(Request $request)
    {
        $pharma = $request->user()->pharmacy;

        $query = medicines::whereIn('branch_id', $pharma->branches->pluck('id'))
            ->with('branch');

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('scientific_name', 'like', "%{$search}%");
            });
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by stock status
        if ($request->has('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->where('quantity', '<=', 10);
            } elseif ($request->stock_status === 'out') {
                $query->where('quantity', 0);
            }
        }

        $medicines = $query->paginate(20);
        return response()->json($medicines);
    }

    /**
     * Get pharmacy orders
     */
    public function orders(Request $request)
    {
        $pharma = $request->user()->pharmacy;

        $query = order::whereIn('branch_id', $pharma->branches->pluck('id'))
            ->with('user', 'branch', 'orderItems.medicine');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $query->latest()->paginate(15);
        return response()->json($orders);
    }

    /**
     * Get sales analytics
     */
    public function analytics(Request $request)
    {
        $pharma = $request->user()->pharmacy;
        $days = $request->get('days', 30);

        $analytics = [
            'sales_trend' => order::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total_price) as revenue')
                )
                ->whereIn('branch_id', $pharma->branches->pluck('id'))
                ->where('created_at', '>=', now()->subDays($days))
                ->where('status', 'completed')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            'branch_comparison' => $pharma->branches->map(function($branch) use ($days) {
                return [
                    'branch' => $branch->name,
                    'orders' => $branch->orders()
                        ->where('status', 'completed')
                        ->where('created_at', '>=', now()->subDays($days))
                        ->count(),
                    'revenue' => $branch->orders()
                        ->where('status', 'completed')
                        ->where('created_at', '>=', now()->subDays($days))
                        ->sum('total_price'),
                ];
            }),

            'category_distribution' => [
                // If you add categories to medicines
            ],
        ];

        return response()->json($analytics);
    }

    /**
     * Update pharmacy profile
     */
    public function updateProfile(Request $request)
    {
        $pharma = $request->user()->pharmacy;

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'main_address' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|unique:pharmas,phone,' . $pharma->id,
        ]);

        $pharma->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'pharmacy' => $pharma
        ]);
    }
}
