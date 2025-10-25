<?php

namespace App\Http\Controllers;

use App\Models\medicines;
use App\Http\Requests\StoreMedicineRequest;
use App\Http\Requests\UpdateMedicineRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MedicineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $medicines = medicines::with('branch', 'pharma')->paginate(15);
        return response()->json($medicines);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMedicineRequest $request)
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('medicines', 'public');
        }

        $medicine = medicines::create($data);
        return response()->json([
            'message' => 'Medicine created successfully',
            'data' => $medicine->load('branch', 'pharma')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(medicines $medicine)
    {
        return response()->json($medicine->load('branch', 'pharma'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMedicineRequest $request, medicines $medicine)
    {
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
            'data' => $medicine->load('branch', 'pharma')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(medicines $medicine)
    {
        // Delete image if exists
        if ($medicine->image) {
            Storage::disk('public')->delete($medicine->image);
        }

        $medicine->delete();
        return response()->json([
            'message' => 'Medicine deleted successfully'
        ]);
    }

    /**
     * Get medicines by branch
     */
    public function getByBranch($branchId)
    {
        $medicines = medicines::where('branch_id', $branchId)
            ->with('branch', 'pharma')
            ->get();
        return response()->json($medicines);
    }

    /**
     * Search medicines
     */
    public function search(Request $request)
    {
        $query = medicines::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('scientific_name')) {
            $query->where('scientific_name', 'like', '%' . $request->scientific_name . '%');
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $medicines = $query->with('branch', 'pharma')->paginate(15);
        return response()->json($medicines);
    }
}
