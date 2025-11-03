<?php

namespace App\Http\Controllers\Pharma;

use App\Http\Controllers\Controller;
use App\Models\order;
use App\Models\order_item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderManagementController extends Controller
{
    /**
     * Get all orders for current pharmacy
     */
    public function index(Request $request)
    {
        $pharma = $request->user()->pharmacy;

        if (!$pharma) {
            return response()->json([
                'message' => 'No pharmacy associated with this account'
            ], 404);
        }

        $query = order::whereIn('branch_id', $pharma->branches->pluck('id'))
            ->with('user', 'branch', 'orderItems.medicine');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $branchId = $request->branch_id;
            
            // Verify branch belongs to pharmacy
            if (!$pharma->branches->contains($branchId)) {
                return response()->json([
                    'message' => 'Branch does not belong to your pharmacy'
                ], 403);
            }
            
            $query->where('branch_id', $branchId);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search by customer name or phone
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $orders = $query->latest()->paginate(15);
        return response()->json($orders);
    }

    /**
     * Get order details
     */
    public function show(Request $request, order $order)
    {
        $pharma = $request->user()->pharmacy;

        // Verify order belongs to pharmacy
        if (!$pharma->branches->pluck('id')->contains($order->branch_id)) {
            return response()->json([
                'message' => 'Unauthorized to view this order'
            ], 403);
        }

        $order->load('user', 'branch', 'orderItems.medicine');

        return response()->json($order);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, order $order)
    {
        $pharma = $request->user()->pharmacy;

        // Verify order belongs to pharmacy
        if (!$pharma->branches->pluck('id')->contains($order->branch_id)) {
            return response()->json([
                'message' => 'Unauthorized to update this order'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,processing,ready,completed,cancelled'
        ]);

        $oldStatus = $order->status;
        $order->update(['status' => $validated['status']]);

        // If order is cancelled, restore medicine quantities
        if ($validated['status'] === 'cancelled' && $oldStatus !== 'cancelled') {
            DB::transaction(function() use ($order) {
                foreach ($order->orderItems as $item) {
                    $medicine = $item->medicine;
                    $medicine->quantity += $item->quantity;
                    $medicine->save();
                }
            });
        }

        // TODO: Send WhatsApp notification to customer about status change

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order->load('orderItems.medicine')
        ]);
    }

    /**
     * Get order statistics
     */
    public function statistics(Request $request)
    {
        $pharma = $request->user()->pharmacy;
        $days = $request->get('days', 30);

        $stats = [
            'total_orders' => order::whereIn('branch_id', $pharma->branches->pluck('id'))
                ->where('created_at', '>=', now()->subDays($days))
                ->count(),

            'orders_by_status' => order::select('status', DB::raw('count(*) as count'))
                ->whereIn('branch_id', $pharma->branches->pluck('id'))
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status'),

            'revenue' => order::whereIn('branch_id', $pharma->branches->pluck('id'))
                ->where('status', 'completed')
                ->where('created_at', '>=', now()->subDays($days))
                ->sum('total_price'),

            'average_order_value' => order::whereIn('branch_id', $pharma->branches->pluck('id'))
                ->where('status', 'completed')
                ->where('created_at', '>=', now()->subDays($days))
                ->avg('total_price'),

            'top_customers' => order::select('user_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(total_price) as total_spent'))
                ->whereIn('branch_id', $pharma->branches->pluck('id'))
                ->where('status', 'completed')
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('user_id')
                ->orderBy('total_spent', 'desc')
                ->take(10)
                ->with('user')
                ->get(),

            'daily_trend' => order::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(total_price) as revenue')
                )
                ->whereIn('branch_id', $pharma->branches->pluck('id'))
                ->where('status', 'completed')
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, order $order)
    {
        $pharma = $request->user()->pharmacy;

        // Verify order belongs to pharmacy
        if (!$pharma->branches->pluck('id')->contains($order->branch_id)) {
            return response()->json([
                'message' => 'Unauthorized to cancel this order'
            ], 403);
        }

        if (in_array($order->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'Cannot cancel ' . $order->status . ' order'
            ], 400);
        }

        DB::transaction(function() use ($order, $request) {
            // Restore medicine quantities
            foreach ($order->orderItems as $item) {
                $medicine = $item->medicine;
                $medicine->quantity += $item->quantity;
                $medicine->save();
            }

            $order->update([
                'status' => 'cancelled',
                'notes' => $request->get('reason', 'Cancelled by pharmacy')
            ]);
        });

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order
        ]);
    }
}
