@extends('layouts.app')

@section('title', 'Medicines')

@section('nav-links')
    <a href="{{ route('pharma.dashboard') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Dashboard
    </a>
    <a href="{{ route('pharma.branches.index') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Branches
    </a>
    <a href="{{ route('pharma.medicines.index') }}" class="border-b-2 border-white text-white inline-flex items-center px-1 pt-1 text-sm font-medium">
        Medicines
    </a>
    <a href="{{ route('pharma.orders.index') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Orders
    </a>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">
            <i class="fas fa-pills mr-2"></i>Medicines Management
        </h1>
        <a href="{{ route('pharma.medicines.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-plus mr-2"></i>Add Medicine
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" name="search" placeholder="Search medicines..." 
                   value="{{ request('search') }}"
                   class="border rounded px-4 py-2">
            <select name="branch_id" class="border rounded px-4 py-2">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->branch_name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
        </form>
    </div>

    <!-- Medicines Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($medicines as $medicine)
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition">
            <!-- Medicine Image -->
            <div class="h-48 bg-gray-200 rounded-t-lg overflow-hidden">
                @if($medicine->image)
                    <img src="{{ asset('storage/' . $medicine->image) }}" alt="{{ $medicine->name }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center">
                        <i class="fas fa-pills text-6xl text-gray-400"></i>
                    </div>
                @endif
            </div>

            <!-- Medicine Details -->
            <div class="p-4">
                <h3 class="font-bold text-lg mb-1">{{ $medicine->name }}</h3>
                <p class="text-sm text-gray-600 mb-2">{{ $medicine->scientific_name }}</p>
                <p class="text-xs text-gray-500 mb-3">{{ $medicine->branch->branch_name }}</p>

                <!-- Stock & Price -->
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <p class="text-sm text-gray-500">Stock</p>
                        <p class="font-bold {{ $medicine->quantity <= 10 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $medicine->quantity }} units
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Price</p>
                        <p class="font-bold text-blue-600">EGP {{ number_format($medicine->price, 2) }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <a href="{{ route('pharma.medicines.edit', $medicine) }}" class="flex-1 bg-blue-600 text-white text-center py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button onclick="showStockModal({{ $medicine->id }}, '{{ $medicine->name }}', {{ $medicine->quantity }})" 
                            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        <i class="fas fa-box"></i>
                    </button>
                    <form action="{{ route('pharma.medicines.destroy', $medicine) }}" method="POST" 
                          onsubmit="return confirm('Are you sure?')" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-3 text-center py-12 text-gray-500">
            No medicines found
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $medicines->links() }}
    </div>
</div>

<!-- Stock Update Modal -->
<div id="stockModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-lg font-bold mb-4">Update Stock</h3>
        <p class="mb-4" id="medicineName"></p>
        
        <form id="stockForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Action</label>
                <select name="action" class="w-full border rounded px-3 py-2" required>
                    <option value="set">Set Stock</option>
                    <option value="add">Add Stock</option>
                    <option value="subtract">Subtract Stock</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Quantity</label>
                <input type="number" name="quantity" class="w-full border rounded px-3 py-2" required min="0">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                    Update
                </button>
                <button type="button" onclick="closeStockModal()" class="flex-1 bg-gray-300 py-2 rounded hover:bg-gray-400">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function showStockModal(id, name, currentStock) {
    document.getElementById('stockModal').classList.remove('hidden');
    document.getElementById('medicineName').textContent = name + ' (Current: ' + currentStock + ' units)';
    document.getElementById('stockForm').action = '/pharma/medicines/' + id + '/update-stock';
}

function closeStockModal() {
    document.getElementById('stockModal').classList.add('hidden');
}
</script>
@endpush
@endsection
