<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProcessAssinaturaWebController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/assinatura/{token}', [ProcessAssinaturaWebController::class, 'show'])->name('assinatura.show');
Route::post('/assinatura/{token}/aprovar', [ProcessAssinaturaWebController::class, 'approve'])->name('assinatura.approve');
Route::post('/assinatura/{token}/reprovar', [ProcessAssinaturaWebController::class, 'reject'])->name('assinatura.reject');
