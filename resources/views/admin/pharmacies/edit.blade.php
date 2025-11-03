@extends('layouts.app')

@section('title', 'Edit Pharmacy')

@section('nav-links')
    <a href="{{ route('admin.dashboard') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Dashboard
    </a>
    <a href="{{ route('admin.users.index') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Users
    </a>
    <a href="{{ route('admin.pharmacies.index') }}" class="border-b-2 border-white text-white inline-flex items-center px-1 pt-1 text-sm font-medium">
        Pharmacies
    </a>
    <a href="{{ route('admin.orders.index') }}" class="border-b-2 border-transparent text-white hover:border-gray-300 inline-flex items-center px-1 pt-1 text-sm font-medium">
        Orders
    </a>
@endsection

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('admin.pharmacies.index') }}" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i>Back to Pharmacies
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">
            <i class="fas fa-hospital mr-2"></i>Edit Pharmacy: {{ $pharmacy->name }}
        </h1>

        <form action="{{ route('admin.pharmacies.update', $pharmacy) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Pharmacy Information -->
            <div class="mb-6 pb-4 border-b">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Pharmacy Information</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Pharmacy Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $pharmacy->name) }}" 
                           class="w-full border rounded-lg px-4 py-2 @error('name') border-red-500 @enderror" 
                           required>
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Main Address <span class="text-red-500">*</span>
                    </label>
                    <textarea name="main_address" rows="2" 
                              class="w-full border rounded-lg px-4 py-2 @error('main_address') border-red-500 @enderror" 
                              required>{{ old('main_address', $pharmacy->main_address) }}</textarea>
                    @error('main_address')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email', $pharmacy->email) }}" 
                               class="w-full border rounded-lg px-4 py-2 @error('email') border-red-500 @enderror" 
                               required>
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Phone <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="phone" value="{{ old('phone', $pharmacy->phone) }}" 
                               class="w-full border rounded-lg px-4 py-2 @error('phone') border-red-500 @enderror" 
                               required>
                        @error('phone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Owner Information -->
            <div class="mb-6 pb-4 border-b">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Owner Information</h3>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        Current Owner: <strong>{{ $pharmacy->user->name }}</strong> ({{ $pharmacy->user->email }})
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Update Owner Password (leave empty to keep current)
                    </label>
                    <input type="password" name="password" 
                           class="w-full border rounded-lg px-4 py-2 @error('password') border-red-500 @enderror">
                    <p class="text-xs text-gray-500 mt-1">Minimum 8 characters. Leave empty to keep current password.</p>
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Confirm New Password
                    </label>
                    <input type="password" name="password_confirmation" 
                           class="w-full border rounded-lg px-4 py-2">
                </div>
            </div>

            <!-- Submit -->
            <div class="flex gap-4">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold">
                    <i class="fas fa-save mr-2"></i>Update Pharmacy
                </button>
                <a href="{{ route('admin.pharmacies.index') }}" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 py-3 rounded-lg font-semibold text-center">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
