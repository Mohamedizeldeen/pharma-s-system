@extends('layouts.app')

@section('title', 'Pharmacy Details')

@section('nav-links')
    <a href="{{ route('admin.dashboard') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Dashboard
    </a>
    <a href="{{ route('admin.users.index') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Users
    </a>
    <a href="{{ route('admin.pharmacies.index') }}" class="border-b-2 border-white text-white inline-flex items-center px-1 pt-1 text-sm font-medium">
        Pharmacies
    </a>
    <a href="{{ route('admin.orders.index') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Orders
    </a>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('admin.pharmacies.index') }}" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i>Back to Pharmacies
        </a>
    </div>

    <!-- Pharmacy Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-hospital mr-2"></i>{{ $pharmacy->name }}
                </h1>
                <p class="text-gray-600">{{ $pharmacy->main_address }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.pharmacies.edit', $pharmacy) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Branches</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['total_branches'] }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-store text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Medicines</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['total_medicines'] }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-pills text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Orders</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['total_orders'] }}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <i class="fas fa-shopping-cart text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Revenue</p>
                    <p class="text-2xl font-bold text-gray-900">EGP {{ number_format($stats['total_revenue'], 0) }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <i class="fas fa-money-bill-wave text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Contact Information -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">
                <i class="fas fa-info-circle mr-2"></i>Contact Information
            </h2>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-500">Email</p>
                    <p class="font-medium text-gray-900">{{ $pharmacy->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Phone</p>
                    <p class="font-medium text-gray-900">{{ $pharmacy->phone }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Owner</p>
                    <p class="font-medium text-gray-900">{{ $pharmacy->user->name }}</p>
                    <p class="text-sm text-gray-600">{{ $pharmacy->user->email }}</p>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">
                <i class="fas fa-shopping-cart mr-2"></i>Recent Orders
            </h2>
            <div class="space-y-2">
                @forelse($stats['recent_orders'] as $order)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <div>
                        <p class="font-medium text-sm">Order #{{ $order->id }}</p>
                        <p class="text-xs text-gray-600">{{ $order->user->name }}</p>
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

    <!-- Branches List -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-bold text-gray-900">
                <i class="fas fa-store mr-2"></i>Branches ({{ $pharmacy->branches->count() }})
            </h2>
        </div>
        <div class="p-6">
            @if($pharmacy->branches->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($pharmacy->branches as $branch)
                    <div class="border rounded-lg p-4 hover:shadow-md transition">
                        <h3 class="font-bold text-lg mb-2">{{ $branch->branch_name }}</h3>
                        <p class="text-sm text-gray-600 mb-2">
                            <i class="fas fa-map-marker-alt mr-1"></i>{{ Str::limit($branch->address, 50) }}
                        </p>
                        <p class="text-sm text-gray-600 mb-2">
                            <i class="fas fa-phone mr-1"></i>{{ $branch->phone }}
                        </p>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-clock mr-1"></i>{{ $branch->open_time }} - {{ $branch->close_time }}
                        </p>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-center text-gray-500 py-8">
                    <i class="fas fa-store text-4xl mb-2 opacity-50"></i><br>
                    No branches added yet
                </p>
            @endif
        </div>
    </div>
</div>
@endsection
