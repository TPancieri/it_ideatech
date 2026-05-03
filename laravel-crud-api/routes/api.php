<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProcessInvitationController;
use App\Http\Controllers\ProcessoController;
use App\Http\Controllers\ProcessoSignatarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [ApiAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [ApiAuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('cliente', ClienteController::class);
    Route::post('processo/{processo}/document', [ProcessoController::class, 'uploadDocument']);
    Route::get('processo/{processo}/document', [ProcessoController::class, 'showDocument']);
    Route::get('processo/{processo}/signatarios', [ProcessoSignatarioController::class, 'index']);
    Route::post('processo/{processo}/signatarios', [ProcessoSignatarioController::class, 'store']);
    Route::post('processo/{processo}/signatarios/sync', [ProcessoSignatarioController::class, 'sync']);
    Route::delete('processo/{processo}/signatarios/{cliente}', [ProcessoSignatarioController::class, 'destroy']);
    Route::post('processo/{processo}/convites', [ProcessInvitationController::class, 'enviar']);
    Route::apiResource('processo', ProcessoController::class);
});
