<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller{

    public function index(){
        $cliente = Cliente::all();
        return response()-> json($cliente);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'email'=> 'required|email|unique:clientes,email',
            'role'=> 'required|string',
            'sector'=>'required|string',
            'status'=>'required|in:active,inactive'
        ]);

        $cliente = Cliente::create($validatedData);

        return response()->json($cliente,201);
    }

    public function show (Cliente $cliente){
        return response()->json($cliente);
    }

    public function update(Request $request, Cliente $cliente){
        $validatedData = $request->validate([
        'name'=> 'required|string',
        'email'=>'required|email|unique:clientes,email,' . $cliente->id,
        'role'=> 'required|string',
        'sector'=>'required|string',
        'status'=>'required|in:active,inactive'
        ]);

        $cliente->update($validatedData);

        return response()->json($cliente,200);
    }

    public function destroy(Cliente $cliente){
        #$cliente->delete();

        $cliente->status = 'inactive';
        $cliente->save();

        return response()->json($cliente, 200);
        #return response()->json(null, 204);
    }
}