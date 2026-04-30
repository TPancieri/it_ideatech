<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ControllerCliente;

Route::middleware('auth:sanctum')->get('/user', function (Request $request){
    return $request->user();
});

Route::get( '/clientes', [ControllerCliente::class, 'index']);
Route::post( '/clientes', [ControllerCliente::class, 'store']);
Route::get( '/clientes/{cliente}', [ControllerCliente::class, 'show']);
Route::put( '/clientes/{cliente}', [ControllerCliente::class, 'update']);
Route::delete( '/clientes/{cliente}', [ControllerCliente::class, 'destroy']);