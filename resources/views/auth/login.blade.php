@extends('layouts.guest')

@section('content')
<div class="w-full max-w-md">
    <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
        <!-- Header -->
        <div class="bg-blue-600 px-6 py-8 text-center">
            <i class="fas fa-pills text-white text-5xl mb-4"></i>
            <h1 class="text-white text-2xl font-bold">Pharmacy Management System</h1>
            <p class="text-blue-100 mt-2">Sign in to your account</p>
        </div>

        <!-- Login Form -->
        <div class="px-6 py-8">
            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                <!-- Email -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        <i class="fas fa-envelope mr-2"></i>Email
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email"
                        value="{{ old('email') }}"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required 
                        autofocus
                    >
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                </div>

                <!-- Remember Me -->
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="form-checkbox h-4 w-4 text-blue-600">
                        <span class="ml-2 text-sm text-gray-700">Remember me</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-between">
                    <button 
                        type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                    </button>
                </div>
            </form>

            <!-- Demo Credentials -->
            <div class="mt-6 border-t pt-6">
                <p class="text-xs text-gray-600 text-center mb-3">Demo Accounts:</p>
                <div class="space-y-2 text-xs">
                    <div class="bg-gray-50 p-2 rounded">
                        <strong>Admin:</strong> admin@pharma.com / admin123
                    </div>
                    <div class="bg-gray-50 p-2 rounded">
                        <strong>Pharma:</strong> ahmed@pharma.com / pharma123
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
