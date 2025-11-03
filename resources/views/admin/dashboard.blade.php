@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('nav-links')
    <a href="{{ route('admin.dashboard') }}" class="border-b-2 border-white text-white inline-flex items-center px-1 pt-1 text-sm font-medium">
        Dashboard
    </a>
    <a href="{{ route('admin.users.index') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Users
    </a>
    <a href="{{ route('admin.pharmacies.index') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Pharmacies
    </a>
    <a href="{{ route('admin.orders.index') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Orders
    </a>
    <a href="{{ route('admin.analytics') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Analytics
    </a>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
            <i class="fas fa-tachometer-alt mr-2"></i>Admin Dashboard
        </h1>
        <p class="mt-2 text-gray-600">System overview and statistics</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <!-- Total Users -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Users</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['total_users'] }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-users text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Pharmacies -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Pharmacies</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['total_pharmas'] }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-hospital text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Branches -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Branches</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['total_branches'] }}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <i class="fas fa-store text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Medicines -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Medicines</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['total_medicines'] }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <i class="fas fa-pills text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Orders</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['total_orders'] }}</p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <i class="fas fa-shopping-cart text-red-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-r from-green-400 to-green-600 rounded-lg shadow p-6 text-white">
            <p class="text-green-100 text-sm mb-2">Total Revenue</p>
            <p class="text-3xl font-bold">EGP {{ number_format($stats['revenue']['total'], 2) }}</p>
        </div>
        <div class="bg-gradient-to-r from-blue-400 to-blue-600 rounded-lg shadow p-6 text-white">
            <p class="text-blue-100 text-sm mb-2">Today's Revenue</p>
            <p class="text-3xl font-bold">EGP {{ number_format($stats['revenue']['today'], 2) }}</p>
        </div>
        <div class="bg-gradient-to-r from-purple-400 to-purple-600 rounded-lg shadow p-6 text-white">
            <p class="text-purple-100 text-sm mb-2">This Month</p>
            <p class="text-3xl font-bold">EGP {{ number_format($stats['revenue']['this_month'], 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Recent Orders -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-clock mr-2"></i>Recent Orders
                </h2>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left text-xs font-semibold text-gray-600 uppercase py-2">Order ID</th>
                                <th class="text-left text-xs font-semibold text-gray-600 uppercase py-2">Customer</th>
                                <th class="text-left text-xs font-semibold text-gray-600 uppercase py-2">Amount</th>
                                <th class="text-left text-xs font-semibold text-gray-600 uppercase py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['recent_orders'] as $order)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-3 text-sm">#{{ $order->id }}</td>
                                <td class="py-3 text-sm">{{ $order->user->name }}</td>
                                <td class="py-3 text-sm">EGP {{ number_format($order->total_price, 2) }}</td>
                                <td class="py-3 text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        @if($order->status === 'completed') bg-green-100 text-green-800
                                        @elseif($order->status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                                        @else bg-blue-100 text-blue-800
                                        @endif">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-gray-500">No recent orders</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-exclamation-triangle mr-2 text-orange-500"></i>Low Stock Alerts
                </h2>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @forelse($stats['low_stock_medicines'] as $medicine)
                    <div class="flex items-center justify-between p-3 bg-orange-50 rounded">
                        <div class="flex-1">
                            <p class="font-medium text-sm">{{ $medicine->name }}</p>
                            <p class="text-xs text-gray-600">{{ $medicine->branch->pharma->name }} - {{ $medicine->branch->branch_name }}</p>
                        </div>
                        <span class="px-3 py-1 bg-orange-200 text-orange-800 text-xs font-bold rounded">
                            {{ $medicine->quantity }} left
                        </span>
                    </div>
                    @empty
                    <p class="text-center text-gray-500 py-4">No low stock items</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-user-plus mr-2"></i>Recent Users
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                @foreach($stats['recent_users'] as $user)
                <div class="border rounded-lg p-4 text-center hover:shadow-md transition">
                    <div class="mb-2">
                        <i class="fas fa-user-circle text-4xl text-gray-400"></i>
                    </div>
                    <p class="font-medium text-sm">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                    <span class="inline-block mt-2 px-2 py-1 text-xs rounded-full 
                        @if($user->role === 'admin') bg-red-100 text-red-800
                        @elseif($user->role === 'pharma') bg-blue-100 text-blue-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst($user->role) }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
