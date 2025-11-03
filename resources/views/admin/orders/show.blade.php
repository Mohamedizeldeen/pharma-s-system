@extends('layouts.app')

@section('title', 'Order Details')

@section('nav-links')
    <a href="{{ route('admin.dashboard') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Dashboard
    </a>
    <a href="{{ route('admin.users.index') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Users
    </a>
    <a href="{{ route('admin.pharmacies.index') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Pharmacies
    </a>
    <a href="{{ route('admin.orders.index') }}" class="border-b-2 border-white text-white inline-flex items-center px-1 pt-1 text-sm font-medium">
        Orders
    </a>
@endsection

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('admin.orders.index') }}" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i>Back to Orders
        </a>
    </div>

    <!-- Order Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-receipt mr-2"></i>Order #{{ $order->id }}
                </h1>
                <p class="text-gray-600">Placed on {{ $order->created_at->format('F d, Y \a\t h:i A') }}</p>
            </div>
            <div class="text-right">
                @if($order->status === 'pending')
                    <span class="px-4 py-2 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                        <i class="fas fa-clock mr-1"></i>Pending
                    </span>
                @elseif($order->status === 'confirmed')
                    <span class="px-4 py-2 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                        <i class="fas fa-check mr-1"></i>Confirmed
                    </span>
                @elseif($order->status === 'preparing')
                    <span class="px-4 py-2 rounded-full text-sm font-semibold bg-purple-100 text-purple-800">
                        <i class="fas fa-cog mr-1"></i>Preparing
                    </span>
                @elseif($order->status === 'ready')
                    <span class="px-4 py-2 rounded-full text-sm font-semibold bg-indigo-100 text-indigo-800">
                        <i class="fas fa-box mr-1"></i>Ready for Pickup
                    </span>
                @elseif($order->status === 'completed')
                    <span class="px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>Completed
                    </span>
                @else
                    <span class="px-4 py-2 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                        <i class="fas fa-times-circle mr-1"></i>Cancelled
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Customer Information -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">
                <i class="fas fa-user mr-2"></i>Customer Information
            </h2>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-500">Name</p>
                    <p class="font-medium text-gray-900">{{ $order->user->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Email</p>
                    <p class="font-medium text-gray-900">{{ $order->user->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Phone</p>
                    <p class="font-medium text-gray-900">{{ $order->user->phone ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Address</p>
                    <p class="font-medium text-gray-900">{{ $order->user->address ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Pharmacy Information -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">
                <i class="fas fa-hospital mr-2"></i>Pharmacy Information
            </h2>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-500">Pharmacy</p>
                    <p class="font-medium text-gray-900">{{ $order->branch->pharma->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Branch</p>
                    <p class="font-medium text-gray-900">{{ $order->branch->branch_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Phone</p>
                    <p class="font-medium text-gray-900">{{ $order->branch->phone }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Address</p>
                    <p class="font-medium text-gray-900">{{ $order->branch->address }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Hours</p>
                    <p class="font-medium text-gray-900">{{ $order->branch->open_time }} - {{ $order->branch->close_time }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="px-6 py-4 border-b bg-gray-50">
            <h2 class="text-lg font-bold text-gray-900">
                <i class="fas fa-pills mr-2"></i>Order Items
            </h2>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($order->items as $item)
                <tr>
                    <td class="px-6 py-4">
                        <div>
                            <p class="font-medium text-gray-900">{{ $item->medicine->name }}</p>
                            <p class="text-sm text-gray-500">{{ $item->medicine->scientific_name }}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-900">{{ $item->quantity }}</td>
                    <td class="px-6 py-4 text-gray-900">EGP {{ number_format($item->price, 2) }}</td>
                    <td class="px-6 py-4 font-medium text-gray-900">EGP {{ number_format($item->quantity * $item->price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50">
                <tr>
                    <td colspan="3" class="px-6 py-4 text-right font-bold text-gray-900">Total:</td>
                    <td class="px-6 py-4 font-bold text-gray-900 text-lg">EGP {{ number_format($order->total_price, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Order Timeline -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">
            <i class="fas fa-history mr-2"></i>Order Timeline
        </h2>
        <div class="space-y-4">
            <div class="flex items-start">
                <div class="bg-green-100 rounded-full p-2 mr-4">
                    <i class="fas fa-check text-green-600"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Order Placed</p>
                    <p class="text-sm text-gray-500">{{ $order->created_at->format('F d, Y \a\t h:i A') }}</p>
                </div>
            </div>

            @if($order->updated_at != $order->created_at)
            <div class="flex items-start">
                <div class="bg-blue-100 rounded-full p-2 mr-4">
                    <i class="fas fa-sync text-blue-600"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Last Updated</p>
                    <p class="text-sm text-gray-500">{{ $order->updated_at->format('F d, Y \a\t h:i A') }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
