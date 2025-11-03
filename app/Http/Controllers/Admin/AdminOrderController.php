<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\order;
use App\Models\order_item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOrderController extends Controller
{
    /**
     * Get all orders (admin view)
     */
    public function index(Request $request)
    {
        $query = order::with('user', 'branch.pharma', 'orderItems.medicine');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by pharmacy
        if ($request->has('pharma_id')) {
            $query->whereHas('branch', function($q) use ($request) {
                $q->where('pharma_id', $request->pharma_id);
            });
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $query->latest()->paginate(20);
        return response()->json($orders);
    }

    /**
     * Get order details
     */
    public function show(order $order)
    {
        $order->load('user', 'branch.pharma', 'orderItems.medicine');
        return response()->json($order);
    }

    /**
     * Get comprehensive order statistics
     */
    public function statistics(Request $request)
    {
        $days = $request->get('days', 30);

        $stats = [
            'overview' => [
                'total_orders' => order::where('created_at', '>=', now()->subDays($days))->count(),
                'completed_orders' => order::where('status', 'completed')
                    ->where('created_at', '>=', now()->subDays($days))
                    ->count(),
                'pending_orders' => order::where('status', 'pending')->count(),
                'cancelled_orders' => order::where('status', 'cancelled')
                    ->where('created_at', '>=', now()->subDays($days))
                    ->count(),
            ],

            'revenue' => [
                'total' => order::where('status', 'completed')
                    ->where('created_at', '>=', now()->subDays($days))
                    ->sum('total_price'),
                'average_order_value' => order::where('status', 'completed')
                    ->where('created_at', '>=', now()->subDays($days))
                    ->avg('total_price'),
            ],

            'orders_by_status' => order::select('status', DB::raw('count(*) as count'))
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status'),

            'daily_trend' => order::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(total_price) as revenue')
                )
                ->where('created_at', '>=', now()->subDays($days))
                ->where('status', 'completed')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            'top_branches' => order::select('branch_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(total_price) as revenue'))
                ->with('branch.pharma')
                ->where('status', 'completed')
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('branch_id')
                ->orderBy('revenue', 'desc')
                ->take(10)
                ->get(),

            'top_customers' => order::select('user_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(total_price) as total_spent'))
                ->with('user')
                ->where('status', 'completed')
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('user_id')
                ->orderBy('total_spent', 'desc')
                ->take(10)
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Get top selling medicines across all pharmacies
     */
    public function topSellingMedicines(Request $request)
    {
        $days = $request->get('days', 30);

        $topMedicines = DB::table('medicines')
            ->select(
                'medicines.*',
                'branches.branch_name',
                'pharmas.name as pharmacy_name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->join('order_items', 'medicines.id', '=', 'order_items.medicine_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('branches', 'medicines.branch_id', '=', 'branches.id')
            ->join('pharmas', 'branches.pharma_id', '=', 'pharmas.id')
            ->where('orders.status', 'completed')
            ->where('orders.created_at', '>=', now()->subDays($days))
            ->groupBy('medicines.id', 'branches.branch_name', 'pharmas.name')
            ->orderBy('total_sold', 'desc')
            ->take(20)
            ->get();

        return response()->json($topMedicines);
    }
}
