<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FreelancePayrollController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CommissionSettingsController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TransactionController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    if (! Schema::hasTable('users')) {
        return response('', 200);
    }

    if (! User::query()->exists()) {
        return redirect()->route('owner.setup.create');
    }

    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/daily', [ReportController::class, 'daily'])->name('daily');
        Route::get('/daily/export/csv', [ReportController::class, 'exportDailyCsv'])->name('daily.export.csv');
        Route::get('/monthly', [ReportController::class, 'monthly'])->name('monthly');
        Route::get('/monthly/export/csv', [ReportController::class, 'exportMonthlyCsv'])->name('monthly.export.csv');
        Route::get('/payment', [ReportController::class, 'payment'])->name('payment');
        Route::get('/products', [ReportController::class, 'products'])->name('products');
        Route::get('/products/export/csv', [ReportController::class, 'exportProductsCsv'])->name('products.export.csv');
        Route::get('/employees', [ReportController::class, 'employees'])->name('employees');
        Route::get('/employees/export/csv', [ReportController::class, 'exportEmployeesCsv'])->name('employees.export.csv');
    });

    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/daily-batch', [TransactionController::class, 'createDailyBatch'])->name('daily-batch.create');
        Route::post('/daily-batch', [TransactionController::class, 'storeDailyBatch'])->name('daily-batch.store');
        Route::get('/{transaction}/edit', [TransactionController::class, 'edit'])->name('edit');
        Route::put('/{transaction}', [TransactionController::class, 'update'])->name('update');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
        Route::delete('/{transaction}', [TransactionController::class, 'destroy'])->name('destroy');
    });
    Route::get('/payroll/freelance', [FreelancePayrollController::class, 'index'])->name('payroll.freelance.index');
    Route::post('/payroll/freelance/payments/prepare', [FreelancePayrollController::class, 'preparePayment'])->name('payroll.freelance.prepare-payment');
    Route::resource('payroll', PayrollController::class)->only(['index', 'show']);
    Route::post('/payroll/open', [PayrollController::class, 'open'])->name('payroll.open');
    Route::post('/payroll/{payroll}/close', [PayrollController::class, 'close'])->name('payroll.close');

    Route::resource('employees', EmployeeController::class);
    Route::resource('services', ServiceController::class);
    Route::resource('products', ProductController::class);
    Route::resource('expenses', ExpenseController::class);
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/commission', [CommissionSettingsController::class, 'edit'])->name('commission.edit');
        Route::put('/commission', [CommissionSettingsController::class, 'update'])->name('commission.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
