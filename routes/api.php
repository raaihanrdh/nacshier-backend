<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionItemController;
use App\Http\Controllers\TransactionExportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CashflowController;
use App\Http\Controllers\CashflowExportController;
use App\Http\Controllers\ProfitController;
use App\Http\Controllers\ProfitExportController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ShiftController;

/**
 * ========================
 * PUBLIC ROUTES (No Auth)
 * ========================
 */

// Authentication
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->middleware('throttle:5,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

/**
 * ========================
 * PROTECTED ROUTES (Auth)
 * ========================
 */

Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/user-profile', [AuthController::class, 'getUserProfile']);

    // Shift Management (untuk kasir)
    Route::prefix('shift')->group(function() {
        Route::get('/active', [ShiftController::class, 'getActiveShift']);
        Route::get('/get-or-create', [ShiftController::class, 'getOrCreateShift']);
        Route::post('/close', [ShiftController::class, 'closeShift']);
    });

    // User Management (Admin Only)
    Route::middleware('admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
    });

    // Transactions
    Route::prefix('transactions')->group(function() {
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('/export/excel', [TransactionExportController::class, 'exportExcel'])->name('transactions.export.excel');
        Route::get('/export/pdf', [TransactionExportController::class, 'exportPDF'])->name('transactions.export.pdf');
        Route::post('/', [TransactionController::class, 'store']);
        Route::get('{id}', [TransactionController::class, 'show']);
        Route::put('{id}', [TransactionController::class, 'update']);
        Route::delete('{id}', [TransactionController::class, 'destroy']);
        
        // Transaction Items
        Route::prefix('{transactionId}/items')->group(function() {
            Route::get('/', [TransactionItemController::class, 'index']);
            Route::post('/', [TransactionItemController::class, 'store']);
            Route::delete('{itemId}', [TransactionItemController::class, 'destroy']);
        });
    });

    // Products
    Route::apiResource('products', ProductController::class);
    Route::delete('/products/{id}/image', [ProductController::class, 'removeImage']);

    // Categories
    Route::apiResource('categories', CategoryController::class);
    Route::get('/categories/{category_id}/products', [CategoryController::class, 'products']);

    // Cashflows
    Route::apiResource('cashflows', CashflowController::class);
    Route::get('/cashflow/summary', [CashflowController::class, 'summary']);
    Route::get('/cashflow/categories', [CashflowController::class, 'getCategories']);
    Route::get('/cashflow/methods', [CashflowController::class, 'getMethods']);
    Route::get('/cashflow/export/pdf', [CashflowExportController::class, 'exportPDF']);
    Route::get('/cashflow/export/excel', [CashflowExportController::class, 'exportExcel']);

    // Profit
    Route::prefix('profit')->group(function() {
        Route::get('/', [ProfitController::class, 'calculateProfit']);
        Route::get('/yearly-chart', [ProfitController::class, 'yearlyProfitChart']);
        Route::get('/comparison', [ProfitController::class, 'profitComparison']);
        Route::get('/export/pdf', [ProfitExportController::class, 'exportPDF']);
        Route::get('/export/excel', [ProfitExportController::class, 'exportExcel']);
    });

    // Dashboard
    Route::prefix('dashboard')->group(function() {
        Route::get('/summary', [DashboardController::class, 'summary']);
        Route::get('/latest-transactions', [DashboardController::class, 'latestTransactions']);
        Route::get('/top-products', [DashboardController::class, 'topProducts']);
        Route::get('/sales-chart', [DashboardController::class, 'salesChart']);
        Route::get('/low-stock', [DashboardController::class, 'lowStockProducts']);
    });

    // Reports
    Route::get('/daily-income', [TransactionController::class, 'dailyIncome']);
    Route::get('/income-report/{period}', [TransactionController::class, 'incomeReport']);
});
