<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\Admin\PharmaController as AdminPharmaController;
use App\Http\Controllers\Web\Admin\UserController as AdminUserController;
use App\Http\Controllers\Web\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Web\Pharma\DashboardController as PharmaDashboardController;
use App\Http\Controllers\Web\Pharma\BranchController as PharmaBranchController;
use App\Http\Controllers\Web\Pharma\MedicineController as PharmaMedicineController;
use App\Http\Controllers\Web\Pharma\OrderController as PharmaOrderController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication
Route::get('login', [AuthController::class, 'showLogin'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.post');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Dashboard Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('analytics', [AdminDashboardController::class, 'analytics'])->name('analytics');
    
    // Users Management
    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    
    // Pharmacies Management
    Route::get('pharmacies', [AdminPharmaController::class, 'index'])->name('pharmacies.index');
    Route::get('pharmacies/create', [AdminPharmaController::class, 'create'])->name('pharmacies.create');
    Route::post('pharmacies', [AdminPharmaController::class, 'store'])->name('pharmacies.store');
    Route::get('pharmacies/{pharma}', [AdminPharmaController::class, 'show'])->name('pharmacies.show');
    Route::get('pharmacies/{pharma}/edit', [AdminPharmaController::class, 'edit'])->name('pharmacies.edit');
    Route::put('pharmacies/{pharma}', [AdminPharmaController::class, 'update'])->name('pharmacies.update');
    Route::delete('pharmacies/{pharma}', [AdminPharmaController::class, 'destroy'])->name('pharmacies.destroy');
    
    // Orders Monitoring
    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
});

/*
|--------------------------------------------------------------------------
| Pharmacy Owner Dashboard Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:pharma'])->prefix('pharma')->name('pharma.')->group(function () {
    
    // Dashboard
    Route::get('dashboard', [PharmaDashboardController::class, 'index'])->name('dashboard');
    Route::get('analytics', [PharmaDashboardController::class, 'analytics'])->name('analytics');
    
    // Branches
    Route::get('branches', [PharmaBranchController::class, 'index'])->name('branches.index');
    Route::get('branches/create', [PharmaBranchController::class, 'create'])->name('branches.create');
    Route::post('branches', [PharmaBranchController::class, 'store'])->name('branches.store');
    Route::get('branches/{branch}/edit', [PharmaBranchController::class, 'edit'])->name('branches.edit');
    Route::put('branches/{branch}', [PharmaBranchController::class, 'update'])->name('branches.update');
    Route::delete('branches/{branch}', [PharmaBranchController::class, 'destroy'])->name('branches.destroy');
    
    // Medicines
    Route::get('medicines', [PharmaMedicineController::class, 'index'])->name('medicines.index');
    Route::get('medicines/create', [PharmaMedicineController::class, 'create'])->name('medicines.create');
    Route::post('medicines', [PharmaMedicineController::class, 'store'])->name('medicines.store');
    Route::get('medicines/{medicine}/edit', [PharmaMedicineController::class, 'edit'])->name('medicines.edit');
    Route::put('medicines/{medicine}', [PharmaMedicineController::class, 'update'])->name('medicines.update');
    Route::delete('medicines/{medicine}', [PharmaMedicineController::class, 'destroy'])->name('medicines.destroy');
    Route::post('medicines/{medicine}/update-stock', [PharmaMedicineController::class, 'updateStock'])->name('medicines.update-stock');
    
    // Orders
    Route::get('orders', [PharmaOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [PharmaOrderController::class, 'show'])->name('orders.show');
    Route::put('orders/{order}/status', [PharmaOrderController::class, 'updateStatus'])->name('orders.update-status');
});
