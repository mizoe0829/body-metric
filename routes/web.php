<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// ダッシュボード
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/metrics', [DashboardController::class, 'store'])->name('metrics.store');
Route::delete('/metrics/{metric}', [DashboardController::class, 'destroy'])->name('metrics.destroy');

// 目標・プロファイル設定
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
