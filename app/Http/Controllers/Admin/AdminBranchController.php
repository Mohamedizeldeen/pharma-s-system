<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\branch;
use App\Models\medicines;
use App\Models\order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBranchController extends Controller
{
    /**
     * Get all branches (admin view)
     */
    public function index(Request $request)
    {
        $query = branch::with(['pharma', 'medicines', 'orders']);

        // Filter by pharmacy
        if ($request->has('pharma_id')) {
            $query->where('pharma_id', $request->pharma_id);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $branches = $query->paginate(20);
        return response()->json($branches);
    }

    /**
     * Get branch details with statistics
     */
    public function show(branch $branch)
    {
        $branch->load(['pharma', 'medicines', 'orders']);

        $stats = [
            'total_medicines' => $branch->medicines->count(),
            'total_orders' => $branch->orders->count(),
            'revenue' => $branch->orders()->where('status', 'completed')->sum('total_price'),
            'pending_orders' => $branch->orders()->where('status', 'pending')->count(),
            'low_stock' => $branch->medicines()->where('quantity', '<=', 10)->count(),
        ];

        return response()->json([
            'branch' => $branch,
            'statistics' => $stats
        ]);
    }

    /**
     * Get branch performance
     */
    public function performance(branch $branch, Request $request)
    {
        $days = $request->get('days', 30);

        $performance = [
            'orders_trend' => order::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(total_price) as revenue')
                )
                ->where('branch_id', $branch->id)
                ->where('created_at', '>=', now()->subDays($days))
                ->where('status', 'completed')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            'top_medicines' => medicines::select('medicines.*', DB::raw('SUM(order_items.quantity) as total_sold'))
                ->join('order_items', 'medicines.id', '=', 'order_items.medicine_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('medicines.branch_id', $branch->id)
                ->where('orders.status', 'completed')
                ->groupBy('medicines.id')
                ->orderBy('total_sold', 'desc')
                ->take(10)
                ->get(),
        ];

        return response()->json($performance);
    }
}
