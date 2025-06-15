<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\MonthlyPerformanceController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SalesPerformanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/transactions/monthly-report', [TransactionReportController::class, 'getMonthlyTransactions']);
Route::get('/performance/monthly-report', [SalesPerformanceController::class, 'getMonthlyPerformance']);
Route::get('/sales/monthly-comparison', [MonthlyPerformanceController::class, 'monthlyPerform']);
Route::apiResource('customers', CustomerController::class);
Route::apiResource('sales', SalesOrderController::class);
