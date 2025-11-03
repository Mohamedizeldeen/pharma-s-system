<?php

namespace App\Http\Controllers\Web\Pharma;

use App\Http\Controllers\Controller;
use App\Models\medicines;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MedicineController extends Controller
{
    /**
     * Display medicines list
     */
    public function index(Request $request)
    {
        $pharma = $request->user()->pharmacy;

        $query = medicines::whereIn('branch_id', $pharma->branches->pluck('id'))
            ->with('branch');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('scientific_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('branch_id') && $request->branch_id != '') {
            $query->where('branch_id', $request->branch_id);
        }

        $medicines = $query->paginate(20);
        $branches = $pharma->branches;

        return view('pharma.medicines.index', compact('medicines', 'branches'));
    }

    /**
     * Show create medicine form
     */
    public function create(Request $request)
    {
        $branches = $request->user()->pharmacy->branches;
        return view('pharma.medicines.create', compact('branches'));
    }

    /**
     * Store new medicine
     */
    public function store(Request $request)
    {
        $pharma = $request->user()->pharmacy;

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'scientific_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048'
        ]);

        // Verify branch belongs to pharmacy
        if (!$pharma->branches->contains($validated['branch_id'])) {
            return back()->with('error', 'Invalid branch selected');
        }

        $validated['pharma_id'] = $pharma->id;

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('medicines', 'public');
        }

        medicines::create($validated);

        return redirect()->route('pharma.medicines.index')
            ->with('success', 'Medicine created successfully');
    }

    /**
     * Show edit medicine form
     */
    public function edit(Request $request, medicines $medicine)
    {
        $pharma = $request->user()->pharmacy;

        if (!$pharma->branches->pluck('id')->contains($medicine->branch_id)) {
            abort(403, 'Unauthorized');
        }

        $branches = $pharma->branches;

        return view('pharma.medicines.edit', compact('medicine', 'branches'));
    }

    /**
     * Update medicine
     */
    public function update(Request $request, medicines $medicine)
    {
        $pharma = $request->user()->pharmacy;

        if (!$pharma->branches->pluck('id')->contains($medicine->branch_id)) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'scientific_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('image')) {
            if ($medicine->image) {
                Storage::disk('public')->delete($medicine->image);
            }
            $validated['image'] = $request->file('image')->store('medicines', 'public');
        }

        $medicine->update($validated);

        return redirect()->route('pharma.medicines.index')
            ->with('success', 'Medicine updated successfully');
    }

    /**
     * Delete medicine
     */
    public function destroy(Request $request, medicines $medicine)
    {
        $pharma = $request->user()->pharmacy;

        if (!$pharma->branches->pluck('id')->contains($medicine->branch_id)) {
            abort(403, 'Unauthorized');
        }

        if ($medicine->image) {
            Storage::disk('public')->delete($medicine->image);
        }

        $medicine->delete();

        return redirect()->route('pharma.medicines.index')
            ->with('success', 'Medicine deleted successfully');
    }

    /**
     * Update stock
     */
    public function updateStock(Request $request, medicines $medicine)
    {
        $pharma = $request->user()->pharmacy;

        if (!$pharma->branches->pluck('id')->contains($medicine->branch_id)) {
            abort(403, 'Unauthorized');
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

        return back()->with('success', 'Stock updated successfully');
    }
}
