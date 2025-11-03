<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\pharma;
use App\Models\branch;
use App\Models\medicines;
use App\Models\order;
use App\Http\Requests\StorePharmaRequest;
use App\Http\Requests\UpdatePharmaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class PharmaManagementController extends Controller
{
    /**
     * Get all pharmacies (admin view)
     */
    public function index(Request $request)
    {
        $query = pharma::with(['user', 'branches']);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status (if you want to add active/inactive)
        if ($request->has('status')) {
            // You can add status field to pharmas table
            // $query->where('status', $request->status);
        }

        $pharmas = $query->paginate(15);
        return response()->json($pharmas);
    }

    /**
     * Create pharmacy with user account (admin only)
     */
    public function store(StorePharmaRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create user account for pharmacy owner
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password ?? 'password123'),
                'role' => 'pharma'
            ]);

            // Create pharmacy
            $pharma = pharma::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'email' => $request->email,
                'main_address' => $request->main_address,
                'phone' => $request->phone,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pharmacy created successfully',
                'pharmacy' => $pharma->load('user', 'branches'),
                'temporary_password' => $request->password ?? 'password123'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create pharmacy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pharmacy details with statistics
     */
    public function show(pharma $pharma)
    {
        $pharma->load(['user', 'branches.medicines', 'branches.orders']);

        $stats = [
            'total_branches' => $pharma->branches->count(),
            'total_medicines' => medicines::whereIn('branch_id', $pharma->branches->pluck('id'))->count(),
            'total_orders' => order::whereIn('branch_id', $pharma->branches->pluck('id'))->count(),
            'total_revenue' => order::whereIn('branch_id', $pharma->branches->pluck('id'))
                ->where('status', 'completed')
                ->sum('total_price'),
        ];

        return response()->json([
            'pharmacy' => $pharma,
            'statistics' => $stats
        ]);
    }

    /**
     * Update pharmacy
     */
    public function update(UpdatePharmaRequest $request, pharma $pharma)
    {
        $pharma->update($request->validated());

        // Update user email if changed
        if ($request->has('email') && $pharma->user) {
            $pharma->user->update(['email' => $request->email]);
        }

        return response()->json([
            'message' => 'Pharmacy updated successfully',
            'pharmacy' => $pharma->load('user', 'branches')
        ]);
    }

    /**
     * Suspend/Activate pharmacy
     */
    public function toggleStatus(pharma $pharma)
    {
        // You'll need to add a 'status' column to pharmas table
        // $pharma->update(['status' => $pharma->status === 'active' ? 'suspended' : 'active']);

        return response()->json([
            'message' => 'Pharmacy status toggled successfully',
            'pharmacy' => $pharma
        ]);
    }

    /**
     * Delete pharmacy (admin only)
     */
    public function destroy(pharma $pharma)
    {
        try {
            DB::beginTransaction();

            // Delete associated user account
            if ($pharma->user) {
                $pharma->user->delete();
            }

            $pharma->delete();

            DB::commit();

            return response()->json([
                'message' => 'Pharmacy deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete pharmacy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pharmacy performance report
     */
    public function performance(pharma $pharma, Request $request)
    {
        $days = $request->get('days', 30);

        $performance = [
            'sales_trend' => order::select(
                    DB::raw('DATE(orders.created_at) as date'),
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(orders.total_price) as revenue')
                )
                ->whereIn('orders.branch_id', $pharma->branches->pluck('id'))
                ->where('orders.created_at', '>=', now()->subDays($days))
                ->where('orders.status', 'completed')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            'top_medicines' => medicines::select('medicines.*', DB::raw('SUM(order_items.quantity) as total_sold'))
                ->join('order_items', 'medicines.id', '=', 'order_items.medicine_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereIn('medicines.branch_id', $pharma->branches->pluck('id'))
                ->where('orders.status', 'completed')
                ->groupBy('medicines.id')
                ->orderBy('total_sold', 'desc')
                ->take(10)
                ->get(),

            'branch_performance' => $pharma->branches->map(function($branch) {
                return [
                    'branch' => $branch,
                    'orders' => $branch->orders()->where('status', 'completed')->count(),
                    'revenue' => $branch->orders()->where('status', 'completed')->sum('total_price'),
                ];
            }),
        ];

        return response()->json($performance);
    }
}
