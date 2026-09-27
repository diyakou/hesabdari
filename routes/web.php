<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Settings\StoreSettingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
    });

    Route::get('/settings/store', [StoreSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings/store', [StoreSettingController::class, 'update'])->name('settings.update');

    Route::resource('parties', \App\Http\Controllers\PartyController::class);
    Route::resource('brands', \App\Http\Controllers\BrandController::class)->except(['show']);
    Route::resource('categories', \App\Http\Controllers\CategoryController::class)->except(['show']);
    Route::resource('products', \App\Http\Controllers\ProductController::class)->only(['index', 'create', 'store']);
    Route::resource('devices', \App\Http\Controllers\DeviceController::class)->only(['index', 'create', 'store', 'show']);

    Route::resource('purchases', \App\Http\Controllers\PurchaseController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/purchases/{invoice}/finalize', [\App\Http\Controllers\PurchaseController::class, 'finalize'])->name('purchases.finalize');

    Route::resource('sales', \App\Http\Controllers\SaleController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/sales/{invoice}/print', [\App\Http\Controllers\SaleController::class, 'print'])->name('sales.print');

    Route::resource('payments', \App\Http\Controllers\PaymentController::class)->only(['index', 'create', 'store', 'show']);

    Route::resource('services', \App\Http\Controllers\ServiceOrderController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('/services/{service}/status', [\App\Http\Controllers\ServiceOrderController::class, 'updateStatus'])->name('services.status');
    Route::resource('service-definitions', \App\Http\Controllers\ServiceDefinitionController::class)->except(['show']);

    Route::get('/returns/create', [\App\Http\Controllers\ReturnController::class, 'create'])->name('returns.create');
    Route::post('/returns', [\App\Http\Controllers\ReturnController::class, 'store'])->name('returns.store');

    Route::resource('expenses', \App\Http\Controllers\ExpenseController::class)->only(['index', 'create', 'store']);
    Route::resource('transfers', \App\Http\Controllers\FundTransferController::class)->only(['index', 'create', 'store']);

    Route::get('/inventory', [\App\Http\Controllers\InventoryAdjustmentController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/adjust', [\App\Http\Controllers\InventoryAdjustmentController::class, 'create'])->name('inventory.adjust');
    Route::post('/inventory/adjust', [\App\Http\Controllers\InventoryAdjustmentController::class, 'store'])->name('inventory.adjust.store');

    Route::prefix('reports')->name('reports.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\ReportController::class, 'index'])->name('index');
        Route::get('/brands', [\App\Http\Controllers\ReportController::class, 'brands'])->name('brands');
        Route::get('/customers', [\App\Http\Controllers\ReportController::class, 'customers'])->name('customers');
        Route::get('/inventory', [\App\Http\Controllers\ReportController::class, 'inventory'])->name('inventory');
        Route::get('/trial-balance', [\App\Http\Controllers\ReportController::class, 'trialBalance'])->name('trial_balance');
        Route::get('/export', [\App\Http\Controllers\ReportController::class, 'export'])->name('export');
    });
});
