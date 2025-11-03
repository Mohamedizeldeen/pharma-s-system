<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\PharmaController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\PharmacyInventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;

// Admin Controllers
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PharmaManagementController;
use App\Http\Controllers\Admin\AdminBranchController;
use App\Http\Controllers\Admin\AdminOrderController;

// Pharma Controllers
use App\Http\Controllers\Pharma\PharmaDashboardController;
use App\Http\Controllers\Pharma\BranchManagementController;
use App\Http\Controllers\Pharma\MedicineManagementController;
use App\Http\Controllers\Pharma\OrderManagementController;

/*
|--------------------------------------------------------------------------
| WhatsApp Webhook Routes (Public)
|--------------------------------------------------------------------------
*/
Route::get('webhook/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('webhook/whatsapp', [WhatsAppWebhookController::class, 'webhook']);

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/
Route::get('medicines-search', [MedicineController::class, 'search']);
Route::get('medicines/branch/{branchId}', [MedicineController::class, 'getByBranch']);

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Authenticated API Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /*
    |--------------------------------------------------------------------------
    | ADMIN ROUTES (Super Admin Only)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        
        // Dashboard & Analytics
        Route::get('dashboard', [AdminDashboardController::class, 'index']);
        Route::get('analytics', [AdminDashboardController::class, 'analytics']);
        
        // User Management
        Route::get('users', [AdminDashboardController::class, 'users']);
        Route::put('users/{user}/role', [AdminDashboardController::class, 'updateUserRole']);
        Route::delete('users/{user}', [AdminDashboardController::class, 'deleteUser']);
        
        // Pharmacy Management
        Route::get('pharmacies', [PharmaManagementController::class, 'index']);
        Route::post('pharmacies', [PharmaManagementController::class, 'store']);
        Route::get('pharmacies/{pharma}', [PharmaManagementController::class, 'show']);
        Route::put('pharmacies/{pharma}', [PharmaManagementController::class, 'update']);
        Route::delete('pharmacies/{pharma}', [PharmaManagementController::class, 'destroy']);
        Route::post('pharmacies/{pharma}/toggle-status', [PharmaManagementController::class, 'toggleStatus']);
        Route::get('pharmacies/{pharma}/performance', [PharmaManagementController::class, 'performance']);
        
        // Branch Monitoring
        Route::get('branches', [AdminBranchController::class, 'index']);
        Route::get('branches/{branch}', [AdminBranchController::class, 'show']);
        Route::get('branches/{branch}/performance', [AdminBranchController::class, 'performance']);
        
        // Order Monitoring & Statistics
        Route::get('orders', [AdminOrderController::class, 'index']);
        Route::get('orders/{order}', [AdminOrderController::class, 'show']);
        Route::get('orders-statistics', [AdminOrderController::class, 'statistics']);
        Route::get('top-selling-medicines', [AdminOrderController::class, 'topSellingMedicines']);
    });

    /*
    |--------------------------------------------------------------------------
    | PHARMACY OWNER ROUTES (Pharma Role)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:pharma')->prefix('pharma')->group(function () {
        
        // Pharmacy Dashboard
        Route::get('dashboard', [PharmaDashboardController::class, 'index']);
        Route::get('analytics', [PharmaDashboardController::class, 'analytics']);
        Route::put('profile', [PharmaDashboardController::class, 'updateProfile']);
        
        // Branch Management
        Route::get('branches', [BranchManagementController::class, 'index']);
        Route::post('branches', [BranchManagementController::class, 'store']);
        Route::get('branches/{branch}', [BranchManagementController::class, 'show']);
        Route::put('branches/{branch}', [BranchManagementController::class, 'update']);
        Route::delete('branches/{branch}', [BranchManagementController::class, 'destroy']);
        
        // Medicine Management
        Route::get('medicines', [MedicineManagementController::class, 'index']);
        Route::post('medicines', [MedicineManagementController::class, 'store']);
        Route::get('medicines/{medicine}', [MedicineManagementController::class, 'show']);
        Route::put('medicines/{medicine}', [MedicineManagementController::class, 'update']);
        Route::delete('medicines/{medicine}', [MedicineManagementController::class, 'destroy']);
        Route::post('medicines/{medicine}/update-stock', [MedicineManagementController::class, 'updateStock']);
        Route::post('medicines/bulk-import', [MedicineManagementController::class, 'bulkImport']);
        
        // Order Management
        Route::get('orders', [OrderManagementController::class, 'index']);
        Route::get('orders/{order}', [OrderManagementController::class, 'show']);
        Route::put('orders/{order}/status', [OrderManagementController::class, 'updateStatus']);
        Route::post('orders/{order}/cancel', [OrderManagementController::class, 'cancel']);
        Route::get('orders-statistics', [OrderManagementController::class, 'statistics']);
    });

    /*
    |--------------------------------------------------------------------------
    | LEGACY ROUTES (For backward compatibility - can be removed later)
    |--------------------------------------------------------------------------
    */
    
    // Pharmas (Pharmacies)
    Route::apiResource('pharmas', PharmaController::class);
    
    // Branches
    Route::apiResource('branches', BranchController::class);
    Route::get('branches/pharma/{pharmaId}', [BranchController::class, 'getByPharma']);
    
    // Medicines
    Route::apiResource('medicines', MedicineController::class);
    
    // Pharmacy Inventories
    Route::apiResource('inventories', PharmacyInventoryController::class);
    Route::get('inventories/branch/{branchId}', [PharmacyInventoryController::class, 'getByBranch']);
    Route::get('inventories/pharmacy/{pharmacyId}', [PharmacyInventoryController::class, 'getByPharmacy']);
    Route::post('inventories/{pharmacyInventory}/update-status', [PharmacyInventoryController::class, 'updateStatus']);
    
    // Orders
    Route::apiResource('orders', OrderController::class);
    Route::get('orders/user/{userId}', [OrderController::class, 'getByUser']);
    Route::get('orders/branch/{branchId}', [OrderController::class, 'getByBranch']);
    Route::post('orders/{order}/update-status', [OrderController::class, 'updateStatus']);
    Route::get('orders-statistics', [OrderController::class, 'statistics']);
    
    // Order Items
    Route::apiResource('order-items', OrderItemController::class)->only(['index', 'show', 'destroy']);
    Route::get('order-items/order/{orderId}', [OrderItemController::class, 'getByOrder']);
});
