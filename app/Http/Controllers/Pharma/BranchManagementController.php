<?php

namespace App\Http\Controllers\Pharma;

use App\Http\Controllers\Controller;
use App\Models\branch;
use App\Models\pharma;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use Illuminate\Http\Request;

class BranchManagementController extends Controller
{
    /**
     * Get all branches for current pharmacy
     */
    public function index(Request $request)
    {
        $pharma = $request->user()->pharmacy;

        if (!$pharma) {
            return response()->json([
                'message' => 'No pharmacy associated with this account'
            ], 404);
        }

        $branches = $pharma->branches()
            ->withCount(['medicines', 'orders'])
            ->get();

        return response()->json($branches);
    }

    /**
     * Create new branch
     */
    public function store(StoreBranchRequest $request)
    {
        $pharma = $request->user()->pharmacy;

        if (!$pharma) {
            return response()->json([
                'message' => 'No pharmacy associated with this account'
            ], 404);
        }

        $branch = branch::create([
            'pharma_id' => $pharma->id,
            'branch_name' => $request->branch_name,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'phone' => $request->phone,
            'open_time' => $request->open_time,
            'close_time' => $request->close_time,
        ]);

        return response()->json([
            'message' => 'Branch created successfully',
            'branch' => $branch
        ], 201);
    }

    /**
     * Get branch details
     */
    public function show(Request $request, branch $branch)
    {
        // Ensure branch belongs to current pharmacy
        if ($branch->pharma_id !== $request->user()->pharmacy->id) {
            return response()->json([
                'message' => 'Unauthorized to view this branch'
            ], 403);
        }

        $branch->load(['medicines', 'orders']);

        return response()->json($branch);
    }

    /**
     * Update branch
     */
    public function update(UpdateBranchRequest $request, branch $branch)
    {
        // Ensure branch belongs to current pharmacy
        if ($branch->pharma_id !== $request->user()->pharmacy->id) {
            return response()->json([
                'message' => 'Unauthorized to update this branch'
            ], 403);
        }

        $branch->update($request->validated());

        return response()->json([
            'message' => 'Branch updated successfully',
            'branch' => $branch
        ]);
    }

    /**
     * Delete branch
     */
    public function destroy(Request $request, branch $branch)
    {
        // Ensure branch belongs to current pharmacy
        if ($branch->pharma_id !== $request->user()->pharmacy->id) {
            return response()->json([
                'message' => 'Unauthorized to delete this branch'
            ], 403);
        }

        // Check if branch has active orders
        if ($branch->orders()->whereIn('status', ['pending', 'processing'])->exists()) {
            return response()->json([
                'message' => 'Cannot delete branch with active orders'
            ], 400);
        }

        $branch->delete();

        return response()->json([
            'message' => 'Branch deleted successfully'
        ]);
    }
}
