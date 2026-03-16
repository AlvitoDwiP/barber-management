<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FreelancePayrollController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/daily', [ReportController::class, 'daily'])->name('daily');
        Route::get('/monthly', [ReportController::class, 'monthly'])->name('monthly');
        Route::get('/payment', [ReportController::class, 'payment'])->name('payment');
        Route::get('/products', [ReportController::class, 'products'])->name('products');
        Route::get('/employees', [ReportController::class, 'employees'])->name('employees');
    });

    Route::get('/transactions/create', fn () => redirect()->route('transactions.daily-batch.create'))->name('transactions.create');
    Route::get('/transactions/daily-batch', [TransactionController::class, 'createDailyBatch'])->name('transactions.daily-batch.create');
    Route::post('/transactions/daily-batch', [TransactionController::class, 'storeDailyBatch'])->name('transactions.daily-batch.store');
    Route::resource('transactions', TransactionController::class)->except(['create', 'store']);
    Route::get('/payroll/freelance', [FreelancePayrollController::class, 'index'])->name('payroll.freelance.index');
    Route::post('/payroll/freelance/payments/prepare', [FreelancePayrollController::class, 'preparePayment'])->name('payroll.freelance.prepare-payment');
    Route::resource('payroll', PayrollController::class)->only(['index', 'show']);
    Route::post('/payroll/open', [PayrollController::class, 'open'])->name('payroll.open');
    Route::post('/payroll/{payroll}/close', [PayrollController::class, 'close'])->name('payroll.close');

    Route::resource('employees', EmployeeController::class);
    Route::resource('services', ServiceController::class);
    Route::resource('products', ProductController::class);
    Route::resource('expenses', ExpenseController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
