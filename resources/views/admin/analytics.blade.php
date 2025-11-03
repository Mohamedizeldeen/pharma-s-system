@extends('layouts.app')

@section('title', 'Analytics')

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
    <a href="{{ route('admin.orders.index') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Orders
    </a>
    <a href="{{ route('admin.analytics') }}" class="border-b-2 border-white text-white inline-flex items-center px-1 pt-1 text-sm font-medium">
        Analytics
    </a>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">
            <i class="fas fa-chart-line mr-2"></i>System Analytics
        </h1>
        <p class="text-gray-600 mt-2">Comprehensive overview of system performance and trends</p>
    </div>

    <!-- Revenue Trends Chart -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">
            <i class="fas fa-dollar-sign mr-2"></i>Revenue Trends (Last 30 Days)
        </h2>
        <canvas id="revenueChart" class="w-full" style="height: 300px;"></canvas>
    </div>

    <!-- Orders & Users Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Orders Trend -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-shopping-cart mr-2"></i>Orders Trend
            </h2>
            <canvas id="ordersChart" style="height: 250px;"></canvas>
        </div>

        <!-- Order Status Distribution -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-chart-pie mr-2"></i>Order Status Distribution
            </h2>
            <canvas id="statusChart" style="height: 250px;"></canvas>
        </div>
    </div>

    <!-- Top Performing Pharmacies -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">
            <i class="fas fa-trophy mr-2"></i>Top Performing Pharmacies
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pharmacy</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Orders</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Order Value</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php
                        $rank = 1;
                    @endphp
                    @foreach($topPharmacies as $pharmacy)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            @if($rank === 1)
                                <span class="text-2xl">🥇</span>
                            @elseif($rank === 2)
                                <span class="text-2xl">🥈</span>
                            @elseif($rank === 3)
                                <span class="text-2xl">🥉</span>
                            @else
                                <span class="text-gray-600 font-bold">#{{ $rank }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ $pharmacy->name }}</p>
                            <p class="text-sm text-gray-500">{{ $pharmacy->branches->count() }} branches</p>
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $pharmacy->orders_count }}</td>
                        <td class="px-6 py-4 font-bold text-green-600">EGP {{ number_format($pharmacy->total_revenue, 2) }}</td>
                        <td class="px-6 py-4 text-gray-900">EGP {{ number_format($pharmacy->total_revenue / max($pharmacy->orders_count, 1), 2) }}</td>
                    </tr>
                    @php $rank++; @endphp
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Trends Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($analytics['revenue_trend']['labels']) !!},
            datasets: [{
                label: 'Revenue (EGP)',
                data: {!! json_encode($analytics['revenue_trend']['data']) !!},
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'EGP ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Orders Trend Chart
    const ordersCtx = document.getElementById('ordersChart').getContext('2d');
    new Chart(ordersCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($analytics['orders_trend']['labels']) !!},
            datasets: [{
                label: 'Orders',
                data: {!! json_encode($analytics['orders_trend']['data']) !!},
                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                borderColor: 'rgb(34, 197, 94)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Order Status Distribution
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($analytics['order_status']['labels']) !!},
            datasets: [{
                data: {!! json_encode($analytics['order_status']['data']) !!},
                backgroundColor: [
                    'rgba(234, 179, 8, 0.7)',   // pending - yellow
                    'rgba(59, 130, 246, 0.7)',  // confirmed - blue
                    'rgba(168, 85, 247, 0.7)',  // preparing - purple
                    'rgba(99, 102, 241, 0.7)',  // ready - indigo
                    'rgba(34, 197, 94, 0.7)',   // completed - green
                    'rgba(239, 68, 68, 0.7)'    // cancelled - red
                ],
                borderColor: [
                    'rgb(234, 179, 8)',
                    'rgb(59, 130, 246)',
                    'rgb(168, 85, 247)',
                    'rgb(99, 102, 241)',
                    'rgb(34, 197, 94)',
                    'rgb(239, 68, 68)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
});
</script>
@endpush
@endsection
