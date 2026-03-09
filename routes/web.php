<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/transactions', fn () => view('dashboard'))->name('transactions.index');
    Route::get('/payrolls', fn () => view('dashboard'))->name('payrolls.index');
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
