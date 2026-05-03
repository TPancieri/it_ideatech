<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PainelController;
use App\Http\Controllers\ProcessAssinaturaWebController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SignatarioWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/login', fn () => redirect()->route('home'))->name('login');

Route::middleware('guest')->group(function (): void {
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::get('/assinatura/{token}', [ProcessAssinaturaWebController::class, 'show'])->name('assinatura.show');
Route::post('/assinatura/{token}/aprovar', [ProcessAssinaturaWebController::class, 'approve'])->name('assinatura.approve');
Route::post('/assinatura/{token}/reprovar', [ProcessAssinaturaWebController::class, 'reject'])->name('assinatura.reject');

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/painel', [PainelController::class, 'index'])->name('painel');

    Route::get('/signatarios', [SignatarioWebController::class, 'index'])->name('signatarios.index');
    Route::get('/signatarios/create', [SignatarioWebController::class, 'create'])->name('signatarios.create');
    Route::post('/signatarios', [SignatarioWebController::class, 'store'])->name('signatarios.store');
    Route::get('/signatarios/{cliente}/edit', [SignatarioWebController::class, 'edit'])->name('signatarios.edit');
    Route::put('/signatarios/{cliente}', [SignatarioWebController::class, 'update'])->name('signatarios.update');
    Route::delete('/signatarios/{cliente}', [SignatarioWebController::class, 'destroy'])->name('signatarios.destroy');

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

    Route::get('/analise', [AnalyticsController::class, 'index'])->name('analytics.index');

    Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
});
