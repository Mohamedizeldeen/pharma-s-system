@extends('layouts.app')

@section('title', 'Pharmacy Dashboard')

@section('nav-links')
    <a href="{{ route('pharma.dashboard') }}" class="border-b-2 border-white text-white inline-flex items-center px-1 pt-1 text-sm font-medium">
        Dashboard
    </a>
    <a href="{{ route('pharma.branches.index') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Branches
    </a>
    <a href="{{ route('pharma.medicines.index') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Medicines
    </a>
    <a href="{{ route('pharma.orders.index') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Orders
    </a>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
            <i class="fas fa-tachometer-alt mr-2"></i>{{ $stats['pharmacy']->name }}
        </h1>
        <p class="mt-2 text-gray-600">{{ $stats['pharmacy']->main_address }}</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Branches -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Branches</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['overview']['total_branches'] }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-store text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Medicines -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Medicines</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['overview']['total_medicines'] }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-pills text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Orders</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['overview']['total_orders'] }}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <i class="fas fa-shopping-cart text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Pending Orders</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['overview']['pending_orders'] }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <i class="fas fa-clock text-yellow-600 text-2xl"></i>
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Orders -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-clock mr-2"></i>Recent Orders
                </h2>
                <a href="{{ route('pharma.orders.index') }}" class="text-blue-600 text-sm hover:text-blue-800">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @forelse($stats['recent_orders'] as $order)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded hover:bg-gray-100">
                        <div class="flex-1">
                            <p class="font-medium text-sm">Order #{{ $order->id }}</p>
                            <p class="text-xs text-gray-600">{{ $order->user->name }} - {{ $order->branch->branch_name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-sm">EGP {{ number_format($order->total_price, 2) }}</p>
                            <span class="text-xs px-2 py-1 rounded 
                                {{ $order->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                   ($order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-gray-500 py-4">No recent orders</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-exclamation-triangle mr-2 text-orange-500"></i>Low Stock Alerts
                </h2>
                <a href="{{ route('pharma.medicines.index') }}" class="text-blue-600 text-sm hover:text-blue-800">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @forelse($stats['low_stock_alerts'] as $medicine)
                    <div class="flex items-center justify-between p-3 bg-orange-50 rounded">
                        <div class="flex-1">
                            <p class="font-medium text-sm">{{ $medicine->name }}</p>
                            <p class="text-xs text-gray-600">{{ $medicine->branch->branch_name }}</p>
                        </div>
                        <span class="px-3 py-1 bg-orange-200 text-orange-800 text-xs font-bold rounded">
                            {{ $medicine->quantity }} left
                        </span>
                    </div>
                    @empty
                    <p class="text-center text-gray-500 py-4">All stock levels are good</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Branches Overview -->
    <div class="mt-8 bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-store mr-2"></i>Your Branches
            </h2>
            <a href="{{ route('pharma.branches.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                <i class="fas fa-plus mr-1"></i>Add Branch
            </a>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($stats['pharmacy']->branches as $branch)
                <div class="border rounded-lg p-4 hover:shadow-md transition">
                    <h3 class="font-bold text-lg mb-2">{{ $branch->branch_name }}</h3>
                    <p class="text-sm text-gray-600 mb-3">
                        <i class="fas fa-phone mr-1"></i>{{ $branch->phone }}
                    </p>
                    <p class="text-sm text-gray-600 mb-3">
                        <i class="fas fa-clock mr-1"></i>{{ $branch->open_time }} - {{ $branch->close_time }}
                    </p>
                    <a href="{{ route('pharma.branches.edit', $branch) }}" class="text-blue-600 text-sm hover:text-blue-800">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
