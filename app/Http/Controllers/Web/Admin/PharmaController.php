<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\pharma;
use App\Models\branch;
use App\Models\order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class PharmaController extends Controller
{
    /**
     * Display pharmacies list
     */
    public function index(Request $request)
    {
        $query = pharma::with(['user', 'branches']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $pharmacies = $query->paginate(15);
        
        return view('admin.pharmacies.index', compact('pharmacies'));
    }

    /**
     * Show create pharmacy form
     */
    public function create()
    {
        return view('admin.pharmacies.create');
    }

    /**
     * Store new pharmacy
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:pharmas',
            'main_address' => 'required|string|max:255',
            'phone' => 'required|string|unique:pharmas',
            'password' => 'required|min:6'
        ]);

        DB::beginTransaction();
        try {
            // Create user account
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'pharma'
            ]);

            // Create pharmacy
            pharma::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'main_address' => $validated['main_address'],
                'phone' => $validated['phone'],
            ]);

            DB::commit();

            return redirect()->route('admin.pharmacies.index')
                ->with('success', 'Pharmacy created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create pharmacy: ' . $e->getMessage());
        }
    }

    /**
     * Show pharmacy details
     */
    public function show(pharma $pharma)
    {
        $pharma->load(['user', 'branches.medicines', 'branches.orders']);

        $stats = [
            'total_branches' => $pharma->branches->count(),
            'total_medicines' => DB::table('medicines')
                ->whereIn('branch_id', $pharma->branches->pluck('id'))
                ->count(),
            'total_orders' => order::whereIn('branch_id', $pharma->branches->pluck('id'))->count(),
            'total_revenue' => order::whereIn('branch_id', $pharma->branches->pluck('id'))
                ->where('status', 'completed')
                ->sum('total_price'),
            'recent_orders' => order::whereIn('branch_id', $pharma->branches->pluck('id'))
                ->with('branch', 'user')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get(),
        ];

        return view('admin.pharmacies.show', compact('pharma', 'stats'));
    }

    /**
     * Show edit pharmacy form
     */
    public function edit(pharma $pharma)
    {
        return view('admin.pharmacies.edit', compact('pharma'));
    }

    /**
     * Update pharmacy
     */
    public function update(Request $request, pharma $pharma)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:pharmas,email,' . $pharma->id,
            'main_address' => 'required|string|max:255',
            'phone' => 'required|string|unique:pharmas,phone,' . $pharma->id,
        ]);

        $pharma->update($validated);

        // Update user email
        if ($pharma->user) {
            $pharma->user->update(['email' => $validated['email']]);
        }

        return redirect()->route('admin.pharmacies.index')
            ->with('success', 'Pharmacy updated successfully');
    }

    /**
     * Delete pharmacy
     */
    public function destroy(pharma $pharma)
    {
        DB::beginTransaction();
        try {
            if ($pharma->user) {
                $pharma->user->delete();
            }

            $pharma->delete();

            DB::commit();

            return redirect()->route('admin.pharmacies.index')
                ->with('success', 'Pharmacy deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete pharmacy: ' . $e->getMessage());
        }
    }
}
