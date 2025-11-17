<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'NaCshier API is running',
        'version' => '1.0.0',
        'status' => 'active',
        'api_documentation' => 'https://api.nacshier.my.id/api',
        'endpoints' => [
            'login' => 'POST /api/login',
            'dashboard' => 'GET /api/dashboard/*',
            'products' => 'GET|POST|PUT|DELETE /api/products',
            'transactions' => 'GET|POST|PUT|DELETE /api/transactions',
            'cashflows' => 'GET|POST|PUT|DELETE /api/cashflows',
            'profit' => 'GET /api/profit',
            'users' => 'GET|POST|PUT|DELETE /api/users (Admin only)',
        ]
    ], 200);
});
