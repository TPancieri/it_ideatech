<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProcessAssinaturaWebController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/assinatura/{token}', [ProcessAssinaturaWebController::class, 'show'])->name('assinatura.show');
Route::post('/assinatura/{token}/aprovar', [ProcessAssinaturaWebController::class, 'approve'])->name('assinatura.approve');
Route::post('/assinatura/{token}/reprovar', [ProcessAssinaturaWebController::class, 'reject'])->name('assinatura.reject');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
Route::get('/dashboard/processo/{processo}', [DashboardController::class, 'show'])->name('dashboard.show');
