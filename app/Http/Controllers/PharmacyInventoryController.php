<?php

namespace App\Http\Controllers;

use App\Models\pharmacy_inventories;
use App\Http\Requests\StorePharmacyInventoryRequest;
use App\Http\Requests\UpdatePharmacyInventoryRequest;
use Illuminate\Http\Request;

class PharmacyInventoryController extends Controller
{  
    public function index()
    {
        $inventories = pharmacy_inventories::with('pharmacy', 'medicine', 'branch')->paginate(15);
        return response()->json($inventories);
    }

    
    public function store(StorePharmacyInventoryRequest $request)
    {
        $inventory = pharmacy_inventories::create($request->validated());
        return response()->json([
            'message' => 'Inventory created successfully',
            'data' => $inventory->load('pharmacy', 'medicine', 'branch')
        ], 201);
    }

    
    public function show(pharmacy_inventories $pharmacyInventory)
    {
        return response()->json($pharmacyInventory->load('pharmacy', 'medicine', 'branch'));
    }

  
    public function update(UpdatePharmacyInventoryRequest $request, pharmacy_inventories $pharmacyInventory)
    {
        $pharmacyInventory->update($request->validated());
        return response()->json([
            'message' => 'Inventory updated successfully',
            'data' => $pharmacyInventory->load('pharmacy', 'medicine', 'branch')
        ]);
    }

    
    public function destroy(pharmacy_inventories $pharmacyInventory)
    {
        $pharmacyInventory->delete();
        return response()->json([
            'message' => 'Inventory deleted successfully'
        ]);
    }

    
    public function getByBranch($branchId)
    {
        $inventories = pharmacy_inventories::where('branch_id', $branchId)
            ->with('pharmacy', 'medicine', 'branch')
            ->get();
        return response()->json($inventories);
    }

    
    public function getByPharmacy($pharmacyId)
    {
        $inventories = pharmacy_inventories::where('pharmacy_id', $pharmacyId)
            ->with('pharmacy', 'medicine', 'branch')
            ->get();
        return response()->json($inventories);
    }

    
    public function updateStatus(pharmacy_inventories $pharmacyInventory)
    {
        $status = $pharmacyInventory->quantity > 0 ? 'in_stock' : 'out_of_stock';
        $pharmacyInventory->update(['status' => $status]);
        
        return response()->json([
            'message' => 'Status updated successfully',
            'data' => $pharmacyInventory
        ]);
    }
}
