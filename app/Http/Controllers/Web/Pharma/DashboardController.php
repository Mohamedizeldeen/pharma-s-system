<?php

namespace App\Http\Controllers\Web\Pharma;

use App\Http\Controllers\Controller;
use App\Models\medicines;
use App\Models\order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display pharmacy dashboard
     */
    public function index(Request $request)
    {
        $pharma = $request->user()->pharmacy;

        if (!$pharma) {
            return redirect()->route('login')
                ->with('error', 'No pharmacy associated with this account');
        }

        $stats = [
            'pharmacy' => $pharma->load('branches'),
            
            'overview' => [
                'total_branches' => $pharma->branches->count(),
                'total_medicines' => medicines::whereIn('branch_id', $pharma->branches->pluck('id'))->count(),
                'total_orders' => order::whereIn('branch_id', $pharma->branches->pluck('id'))->count(),
                'pending_orders' => order::whereIn('branch_id', $pharma->branches->pluck('id'))
                    ->where('status', 'pending')
                    ->count(),
            ],

            'revenue' => [
                'total' => order::whereIn('branch_id', $pharma->branches->pluck('id'))
                    ->where('status', 'completed')
                    ->sum('total_price'),
                'today' => order::whereIn('branch_id', $pharma->branches->pluck('id'))
                    ->where('status', 'completed')
                    ->whereDate('created_at', today())
                    ->sum('total_price'),
                'this_month' => order::whereIn('branch_id', $pharma->branches->pluck('id'))
                    ->where('status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('total_price'),
            ],

            'recent_orders' => order::whereIn('branch_id', $pharma->branches->pluck('id'))
                ->with('user', 'branch', 'orderItems.medicine')
                ->latest()
                ->take(10)
                ->get(),

            'low_stock_alerts' => medicines::whereIn('branch_id', $pharma->branches->pluck('id'))
                ->where('quantity', '<=', 10)
                ->with('branch')
                ->take(10)
                ->get(),
        ];

        return view('pharma.dashboard', compact('stats'));
    }

    /**
     * Display analytics page
     */
    public function analytics(Request $request)
    {
        $pharma = $request->user()->pharmacy;
        $days = $request->get('days', 30);

        $analytics = [
            'sales_trend' => order::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(total_price) as revenue')
                )
                ->whereIn('branch_id', $pharma->branches->pluck('id'))
                ->where('created_at', '>=', now()->subDays($days))
                ->where('status', 'completed')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            'branch_comparison' => $pharma->branches->map(function($branch) use ($days) {
                return [
                    'branch' => $branch->branch_name,
                    'orders' => $branch->orders()
                        ->where('status', 'completed')
                        ->where('created_at', '>=', now()->subDays($days))
                        ->count(),
                    'revenue' => $branch->orders()
                        ->where('status', 'completed')
                        ->where('created_at', '>=', now()->subDays($days))
                        ->sum('total_price'),
                ];
            }),
        ];

        return view('pharma.analytics', compact('analytics', 'days'));
    }
}
