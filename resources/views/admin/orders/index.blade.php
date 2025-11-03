@extends('layouts.app')

@section('title', 'Orders Management')

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
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">
            <i class="fas fa-shopping-cart mr-2"></i>Orders Management
        </h1>
        <p class="text-gray-600 mt-2">Monitor and manage all customer orders</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" placeholder="Search by order ID, customer..." 
                   value="{{ request('search') }}"
                   class="border rounded px-4 py-2">
            <select name="status" class="border rounded px-4 py-2">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="preparing" {{ request('status') === 'preparing' ? 'selected' : '' }}>Preparing</option>
                <option value="ready" {{ request('status') === 'ready' ? 'selected' : '' }}>Ready</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <input type="date" name="date" value="{{ request('date') }}" class="border rounded px-4 py-2">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Orders</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $orders->total() }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $orders->where('status', 'pending')->count() }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Completed</p>
                    <p class="text-2xl font-bold text-green-600">{{ $orders->where('status', 'completed')->count() }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Revenue</p>
                    <p class="text-2xl font-bold text-purple-600">
                        EGP {{ number_format($orders->sum('total_price'), 0) }}
                    </p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <i class="fas fa-money-bill-wave text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Branch</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($orders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <span class="font-bold text-blue-600">#{{ $order->id }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <div>
                            <p class="font-medium text-gray-900">{{ $order->user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $order->user->email }}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <div>
                            <p class="font-medium text-gray-900">{{ $order->branch->branch_name }}</p>
                            <p class="text-xs text-gray-500">{{ $order->branch->pharma->name }}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <p class="font-bold text-gray-900">EGP {{ number_format($order->total_price, 2) }}</p>
                    </td>
                    <td class="px-6 py-4">
                        @if($order->status === 'pending')
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                <i class="fas fa-clock mr-1"></i>Pending
                            </span>
                        @elseif($order->status === 'confirmed')
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                <i class="fas fa-check mr-1"></i>Confirmed
                            </span>
                        @elseif($order->status === 'preparing')
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                                <i class="fas fa-cog mr-1"></i>Preparing
                            </span>
                        @elseif($order->status === 'ready')
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                <i class="fas fa-box mr-1"></i>Ready
                            </span>
                        @elseif($order->status === 'completed')
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Completed
                            </span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>Cancelled
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $order->created_at->format('M d, Y') }}<br>
                        <span class="text-xs">{{ $order->created_at->format('h:i A') }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-shopping-cart text-4xl mb-2 opacity-50"></i>
                        <p>No orders found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $orders->links() }}
    </div>
</div>
@endsection
