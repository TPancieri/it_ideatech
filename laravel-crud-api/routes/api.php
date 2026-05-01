<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProcessoController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request){
    return $request->user();
});

Route::apiResource('cliente', ClienteController::class);
Route::post('processo/{processo}/document', [ProcessoController::class, 'uploadDocument']);
Route::get('processo/{processo}/document', [ProcessoController::class, 'showDocument']);
Route::apiResource('processo', ProcessoController::class);
#Route::get('/cliente', [ClienteController::class, 'index']);
#Route::post('/cliente', [ClienteController::class, 'store']);
#Route::get('/cliente/{cliente}', [ClienteController::class, 'show']);
#Route::put('/cliente/{cliente}', [ClienteController::class, 'update']);
#Route::delete('/cliente/{cliente}', [ClienteController::class, 'destroy']);
