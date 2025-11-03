<?php

namespace App\Http\Controllers\Web\Pharma;

use App\Http\Controllers\Controller;
use App\Models\order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display orders list
     */
    public function index(Request $request)
    {
        $pharma = $request->user()->pharmacy;

        $query = order::whereIn('branch_id', $pharma->branches->pluck('id'))
            ->with('user', 'branch', 'orderItems.medicine');

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('branch_id') && $request->branch_id != '') {
            $query->where('branch_id', $request->branch_id);
        }

        $orders = $query->latest()->paginate(15);
        $branches = $pharma->branches;

        return view('pharma.orders.index', compact('orders', 'branches'));
    }

    /**
     * Show order details
     */
    public function show(Request $request, order $order)
    {
        $pharma = $request->user()->pharmacy;

        if (!$pharma->branches->pluck('id')->contains($order->branch_id)) {
            abort(403, 'Unauthorized');
        }

        $order->load('user', 'branch', 'orderItems.medicine');

        return view('pharma.orders.show', compact('order'));
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, order $order)
    {
        $pharma = $request->user()->pharmacy;

        if (!$pharma->branches->pluck('id')->contains($order->branch_id)) {
            abort(403, 'Unauthorized');
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

        return back()->with('success', 'Order status updated successfully');
    }
}
