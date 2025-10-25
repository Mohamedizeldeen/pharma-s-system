<?php

namespace App\Http\Controllers;

use App\Models\order_item;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orderItems = order_item::with('order', 'medicine')->paginate(15);
        return response()->json($orderItems);
    }

    /**
     * Display the specified resource.
     */
    public function show(order_item $orderItem)
    {
        return response()->json($orderItem->load('order', 'medicine'));
    }

    /**
     * Get order items by order
     */
    public function getByOrder($orderId)
    {
        $orderItems = order_item::where('order_id', $orderId)
            ->with('order', 'medicine')
            ->get();
        return response()->json($orderItems);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(order_item $orderItem)
    {
        $orderItem->delete();
        return response()->json([
            'message' => 'Order item deleted successfully'
        ]);
    }
}
