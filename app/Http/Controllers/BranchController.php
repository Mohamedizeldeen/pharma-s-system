<?php

namespace App\Http\Controllers;

use App\Models\branch;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    
    public function index()
    {
        $branches = branch::with('pharma')->paginate(15);
        return response()->json($branches);
    }

   
    public function store(StoreBranchRequest $request)
    {
        $branch = branch::create($request->validated());
        return response()->json([
            'message' => 'Branch created successfully',
            'data' => $branch->load('pharma')
        ], 201);
    }

  
    public function show(branch $branch)
    {
        return response()->json($branch->load('pharma'));
    }

    
    public function update(UpdateBranchRequest $request, branch $branch)
    {
        $branch->update($request->validated());
        return response()->json([
            'message' => 'Branch updated successfully',
            'data' => $branch->load('pharma')
        ]);
    }

 
    public function destroy(branch $branch)
    {
        $branch->delete();
        return response()->json([
            'message' => 'Branch deleted successfully'
        ]);
    }

  
    public function getByPharma($pharmaId)
    {
        $branches = branch::where('pharma_id', $pharmaId)->with('pharma')->get();
        return response()->json($branches);
    }
}
