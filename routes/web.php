<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('transactions', TransactionController::class);
    Route::resource('payroll', PayrollController::class)->only(['index', 'show']);
    Route::post('/payroll/open', [PayrollController::class, 'open'])->name('payroll.open');
    Route::post('/payroll/{payroll}/close', [PayrollController::class, 'close'])->name('payroll.close');
    Route::get('/reports', fn () => view('dashboard'))->name('reports.index');

    Route::resource('employees', EmployeeController::class);
    Route::resource('services', ServiceController::class);
    Route::resource('products', ProductController::class);
    Route::resource('expenses', ExpenseController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
