<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\pharma;
use App\Models\branch;
use App\Models\medicines;
use App\Models\order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display admin dashboard
     */
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_pharmas' => pharma::count(),
            'total_branches' => branch::count(),
            'total_medicines' => medicines::count(),
            'total_orders' => order::count(),
            
            'users_by_role' => User::select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->get()
                ->pluck('count', 'role'),
            
            'recent_users' => User::latest()->take(5)->get(),
            
            'recent_orders' => order::with('user', 'branch.pharma')
                ->latest()
                ->take(10)
                ->get(),
            
            'orders_by_status' => order::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status'),
            
            'revenue' => [
                'total' => order::where('status', 'completed')->sum('total_price'),
                'today' => order::where('status', 'completed')
                    ->whereDate('created_at', today())
                    ->sum('total_price'),
                'this_month' => order::where('status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('total_price'),
            ],
            
            'low_stock_medicines' => medicines::where('quantity', '<=', 10)
                ->with('branch.pharma')
                ->take(10)
                ->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    /**
     * Display analytics page
     */
    public function analytics(Request $request)
    {
        $days = $request->get('days', 30);

        $analytics = [
            'orders_trend' => order::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(total_price) as revenue')
                )
                ->where('created_at', '>=', now()->subDays($days))
                ->where('status', 'completed')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            'pharma_performance' => pharma::select(
                    'pharmas.id',
                    'pharmas.name',
                    DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                    DB::raw('SUM(orders.total_price) as total_revenue')
                )
                ->join('branches', 'pharmas.id', '=', 'branches.pharma_id')
                ->join('orders', 'branches.id', '=', 'orders.branch_id')
                ->where('orders.status', 'completed')
                ->groupBy('pharmas.id', 'pharmas.name')
                ->orderBy('total_revenue', 'desc')
                ->get(),
        ];

        return view('admin.analytics', compact('analytics', 'days'));
    }
}
