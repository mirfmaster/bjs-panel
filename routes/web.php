<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'showDashboard'])->name('dashboard');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update']);

    Route::get('/orders', [OrdersController::class, 'index'])->name('orders.index');
    Route::get('/orders/stats', [OrdersController::class, 'stats'])->name('orders.stats');
    Route::get('/orders/{order}', [OrdersController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/start', [OrdersController::class, 'startOrder'])->name('orders.start');
    Route::post('/orders/{order}/complete', [OrdersController::class, 'complete'])->name('orders.complete');
    Route::post('/orders/{order}/cancel', [OrdersController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/partial', [OrdersController::class, 'partial'])->name('orders.partial');
    Route::post('/orders/{order}/set-remains', [OrdersController::class, 'setRemains'])->name('orders.set-remains');
});

Route::get('/', fn() => redirect()->route('dashboard'));
