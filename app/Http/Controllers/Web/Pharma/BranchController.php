<?php

namespace App\Http\Controllers\Web\Pharma;

use App\Http\Controllers\Controller;
use App\Models\branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * Display branches list
     */
    public function index(Request $request)
    {
        $pharma = $request->user()->pharmacy;

        $branches = $pharma->branches()
            ->withCount(['medicines', 'orders'])
            ->get();

        return view('pharma.branches.index', compact('branches'));
    }

    /**
     * Show create branch form
     */
    public function create()
    {
        return view('pharma.branches.create');
    }

    /**
     * Store new branch
     */
    public function store(Request $request)
    {
        $pharma = $request->user()->pharmacy;

        $validated = $request->validate([
            'branch_name' => 'required|string|max:255',
            'longitude' => 'required|numeric',
            'latitude' => 'required|numeric',
            'phone' => 'required|string',
            'open_time' => 'required',
            'close_time' => 'required',
        ]);

        $validated['pharma_id'] = $pharma->id;

        branch::create($validated);

        return redirect()->route('pharma.branches.index')
            ->with('success', 'Branch created successfully');
    }

    /**
     * Show edit branch form
     */
    public function edit(Request $request, branch $branch)
    {
        if ($branch->pharma_id !== $request->user()->pharmacy->id) {
            abort(403, 'Unauthorized');
        }

        return view('pharma.branches.edit', compact('branch'));
    }

    /**
     * Update branch
     */
    public function update(Request $request, branch $branch)
    {
        if ($branch->pharma_id !== $request->user()->pharmacy->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'branch_name' => 'required|string|max:255',
            'longitude' => 'required|numeric',
            'latitude' => 'required|numeric',
            'phone' => 'required|string',
            'open_time' => 'required',
            'close_time' => 'required',
        ]);

        $branch->update($validated);

        return redirect()->route('pharma.branches.index')
            ->with('success', 'Branch updated successfully');
    }

    /**
     * Delete branch
     */
    public function destroy(Request $request, branch $branch)
    {
        if ($branch->pharma_id !== $request->user()->pharmacy->id) {
            abort(403, 'Unauthorized');
        }

        if ($branch->orders()->whereIn('status', ['pending', 'processing'])->exists()) {
            return back()->with('error', 'Cannot delete branch with active orders');
        }

        $branch->delete();

        return redirect()->route('pharma.branches.index')
            ->with('success', 'Branch deleted successfully');
    }
}
