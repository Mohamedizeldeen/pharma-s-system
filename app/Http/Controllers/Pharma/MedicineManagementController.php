<?php

namespace App\Http\Controllers\Pharma;

use App\Http\Controllers\Controller;
use App\Models\medicines;
use App\Models\branch;
use App\Http\Requests\StoreMedicineRequest;
use App\Http\Requests\UpdateMedicineRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MedicineManagementController extends Controller
{
    /**
     * Get all medicines for current pharmacy
     */
    public function index(Request $request)
    {
        $pharma = $request->user()->pharmacy;

        if (!$pharma) {
            return response()->json([
                'message' => 'No pharmacy associated with this account'
            ], 404);
        }

        $query = medicines::whereIn('branch_id', $pharma->branches->pluck('id'))
            ->with('branch');

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('scientific_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $branchId = $request->branch_id;
            
            // Verify branch belongs to pharmacy
            if (!$pharma->branches->contains($branchId)) {
                return response()->json([
                    'message' => 'Branch does not belong to your pharmacy'
                ], 403);
            }
            
            $query->where('branch_id', $branchId);
        }

        // Filter by stock status
        if ($request->has('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->where('quantity', '>', 0)->where('quantity', '<=', 10);
            } elseif ($request->stock_status === 'out') {
                $query->where('quantity', 0);
            } elseif ($request->stock_status === 'available') {
                $query->where('quantity', '>', 10);
            }
        }

        $medicines = $query->paginate(20);
        return response()->json($medicines);
    }

    /**
     * Create new medicine
     */
    public function store(StoreMedicineRequest $request)
    {
        $pharma = $request->user()->pharmacy;

        // Verify branch belongs to pharmacy
        $branch = branch::find($request->branch_id);
        if (!$branch || $branch->pharma_id !== $pharma->id) {
            return response()->json([
                'message' => 'Invalid branch or unauthorized'
            ], 403);
        }

        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('medicines', 'public');
        }

        $medicine = medicines::create($data);

        return response()->json([
            'message' => 'Medicine added successfully',
            'medicine' => $medicine->load('branch')
        ], 201);
    }

    /**
     * Get medicine details
     */
    public function show(Request $request, medicines $medicine)
    {
        $pharma = $request->user()->pharmacy;

        // Verify medicine belongs to pharmacy
        if (!$pharma->branches->pluck('id')->contains($medicine->branch_id)) {
            return response()->json([
                'message' => 'Unauthorized to view this medicine'
            ], 403);
        }

        $medicine->load('branch');

        return response()->json($medicine);
    }

    /**
     * Update medicine
     */
    public function update(UpdateMedicineRequest $request, medicines $medicine)
    {
        $pharma = $request->user()->pharmacy;

        // Verify medicine belongs to pharmacy
        if (!$pharma->branches->pluck('id')->contains($medicine->branch_id)) {
            return response()->json([
                'message' => 'Unauthorized to update this medicine'
            ], 403);
        }

        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($medicine->image) {
                Storage::disk('public')->delete($medicine->image);
            }
            
            $data['image'] = $request->file('image')->store('medicines', 'public');
        }

        $medicine->update($data);

        return response()->json([
            'message' => 'Medicine updated successfully',
            'medicine' => $medicine->load('branch')
        ]);
    }

    /**
     * Update medicine stock quantity
     */
    public function updateStock(Request $request, medicines $medicine)
    {
        $pharma = $request->user()->pharmacy;

        // Verify medicine belongs to pharmacy
        if (!$pharma->branches->pluck('id')->contains($medicine->branch_id)) {
            return response()->json([
                'message' => 'Unauthorized to update this medicine'
            ], 403);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'action' => 'required|in:set,add,subtract'
        ]);

        if ($validated['action'] === 'set') {
            $medicine->quantity = $validated['quantity'];
        } elseif ($validated['action'] === 'add') {
            $medicine->quantity += $validated['quantity'];
        } elseif ($validated['action'] === 'subtract') {
            $medicine->quantity = max(0, $medicine->quantity - $validated['quantity']);
        }

        $medicine->save();

        return response()->json([
            'message' => 'Stock updated successfully',
            'medicine' => $medicine
        ]);
    }

    /**
     * Delete medicine
     */
    public function destroy(Request $request, medicines $medicine)
    {
        $pharma = $request->user()->pharmacy;

        // Verify medicine belongs to pharmacy
        if (!$pharma->branches->pluck('id')->contains($medicine->branch_id)) {
            return response()->json([
                'message' => 'Unauthorized to delete this medicine'
            ], 403);
        }

        // Delete image
        if ($medicine->image) {
            Storage::disk('public')->delete($medicine->image);
        }

        $medicine->delete();

        return response()->json([
            'message' => 'Medicine deleted successfully'
        ]);
    }

    /**
     * Bulk import medicines from CSV
     */
    public function bulkImport(Request $request)
    {
        $pharma = $request->user()->pharmacy;

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'csv_file' => 'required|file|mimes:csv,txt'
        ]);

        // Verify branch belongs to pharmacy
        $branch = branch::find($request->branch_id);
        if ($branch->pharma_id !== $pharma->id) {
            return response()->json([
                'message' => 'Unauthorized branch'
            ], 403);
        }

        // Process CSV
        $file = $request->file('csv_file');
        $csv = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_shift($csv);

        $imported = 0;
        foreach ($csv as $row) {
            $data = array_combine($header, $row);
            
            medicines::create([
                'branch_id' => $request->branch_id,
                'pharma_id' => $pharma->id,
                'name' => $data['name'] ?? null,
                'scientific_name' => $data['scientific_name'] ?? null,
                'description' => $data['description'] ?? null,
                'quantity' => $data['quantity'] ?? 0,
                'price' => $data['price'] ?? 0,
            ]);

            $imported++;
        }

        return response()->json([
            'message' => "Successfully imported {$imported} medicines",
            'count' => $imported
        ]);
    }
}
