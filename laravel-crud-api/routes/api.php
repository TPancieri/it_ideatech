<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProcessInvitationController;
use App\Http\Controllers\ProcessoController;
use App\Http\Controllers\ProcessoSignatarioController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request){
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


#Route::get('/cliente', [ClienteController::class, 'index']);
#Route::post('/cliente', [ClienteController::class, 'store']);
#Route::get('/cliente/{cliente}', [ClienteController::class, 'show']);
#Route::put('/cliente/{cliente}', [ClienteController::class, 'update']);
#Route::delete('/cliente/{cliente}', [ClienteController::class, 'destroy']);
