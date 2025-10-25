<?php

namespace App\Http\Controllers;

use App\Models\order;
use App\Models\order_item;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = order::with('user', 'branch', 'orderItems.medicine')->paginate(15);
        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        try {
            DB::beginTransaction();

            $orderData = $request->validated();
            $orderItems = $orderData['order_items'];
            unset($orderData['order_items']);

            // Create the order
            $order = order::create($orderData);

            // Create order items
            foreach ($orderItems as $item) {
                $order->orderItems()->create([
                    'medicine_id' => $item['medicine_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully',
                'data' => $order->load('user', 'branch', 'orderItems.medicine')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(order $order)
    {
        return response()->json($order->load('user', 'branch', 'orderItems.medicine'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, order $order)
    {
        $order->update($request->validated());
        return response()->json([
            'message' => 'Order updated successfully',
            'data' => $order->load('user', 'branch', 'orderItems.medicine')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(order $order)
    {
        $order->delete();
        return response()->json([
            'message' => 'Order deleted successfully'
        ]);
    }

    /**
     * Get orders by user
     */
    public function getByUser($userId)
    {
        $orders = order::where('user_id', $userId)
            ->with('user', 'branch', 'orderItems.medicine')
            ->get();
        return response()->json($orders);
    }

    /**
     * Get orders by branch
     */
    public function getByBranch($branchId)
    {
        $orders = order::where('branch_id', $branchId)
            ->with('user', 'branch', 'orderItems.medicine')
            ->get();
        return response()->json($orders);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,canceled'
        ]);

        $order->update(['status' => $request->status]);
        
        return response()->json([
            'message' => 'Order status updated successfully',
            'data' => $order->load('user', 'branch', 'orderItems.medicine')
        ]);
    }

    /**
     * Get order statistics
     */
    public function statistics()
    {
        $stats = [
            'total_orders' => order::count(),
            'pending_orders' => order::where('status', 'pending')->count(),
            'completed_orders' => order::where('status', 'completed')->count(),
            'canceled_orders' => order::where('status', 'canceled')->count(),
            'total_revenue' => order::where('status', 'completed')->sum('total_price'),
        ];

        return response()->json($stats);
    }
}
