<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProcessAssinaturaWebController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportsController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/assinatura/{token}', [ProcessAssinaturaWebController::class, 'show'])->name('assinatura.show');
Route::post('/assinatura/{token}/aprovar', [ProcessAssinaturaWebController::class, 'approve'])->name('assinatura.approve');
Route::post('/assinatura/{token}/reprovar', [ProcessAssinaturaWebController::class, 'reject'])->name('assinatura.reject');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
Route::get('/dashboard/processo/{processo}', [DashboardController::class, 'show'])->name('dashboard.show');

Route::get('/relatorios/status', [ReportsController::class, 'status'])->name('reports.status');
Route::get('/relatorios/status.csv', [ReportsController::class, 'statusCsv'])->name('reports.status.csv');

Route::get('/relatorios/produtividade-signatarios', [ReportsController::class, 'productivity'])->name('reports.productivity');
Route::get('/relatorios/produtividade-signatarios.csv', [ReportsController::class, 'productivityCsv'])->name('reports.productivity.csv');

Route::get('/relatorios/processos-periodo', [ReportsController::class, 'period'])->name('reports.period');
Route::get('/relatorios/processos-periodo.csv', [ReportsController::class, 'periodCsv'])->name('reports.period.csv');

Route::get('/relatorios/reprovacoes', [ReportsController::class, 'rejections'])->name('reports.rejections');
Route::get('/relatorios/reprovacoes.csv', [ReportsController::class, 'rejectionsCsv'])->name('reports.rejections.csv');
