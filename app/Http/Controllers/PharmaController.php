<?php

namespace App\Http\Controllers;

use App\Models\pharma;
use App\Http\Requests\StorePharmaRequest;
use App\Http\Requests\UpdatePharmaRequest;
use Illuminate\Http\Request;

class PharmaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pharmas = pharma::with('user', 'branches')->paginate(15);
        return response()->json($pharmas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePharmaRequest $request)
    {
        $pharma = pharma::create($request->validated());
        return response()->json([
            'message' => 'Pharmacy created successfully',
            'data' => $pharma->load('user', 'branches')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(pharma $pharma)
    {
        return response()->json($pharma->load('user', 'branches'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePharmaRequest $request, pharma $pharma)
    {
        $pharma->update($request->validated());
        return response()->json([
            'message' => 'Pharmacy updated successfully',
            'data' => $pharma->load('user', 'branches')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(pharma $pharma)
    {
        $pharma->delete();
        return response()->json([
            'message' => 'Pharmacy deleted successfully'
        ]);
    }
}
