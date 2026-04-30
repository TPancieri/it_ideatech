<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ControllerCliente extends Controller
{
    public function index()
    {// retornando um .json
    $cliente = Cliente::all();
    return response()->json($cliente);}

    public function store(Request $request)
    {
        $validatedData = $request ->validate([
            'nome' => 'required|string',
            'email' => 'required|string' 
        ]);

        $cliente = Cliente::create($request->all());
        return response()->json($cliente);
    }

    public function show(Cliente $cliente)
    {
        return response()->json($cliente);
    }

    public function update(Request $request, Cliente $cliente)
    {
        $validatedData = $request ->validate([
            'nome' => 'required|string',
            'email' => 'required|string' 
        ]);

        $cliente ->update($validatedData);

        return response()->json($cliente);
    }

    public function destroy (Cliente $cliente)
    {
        $cliente->delete();
        return response()->json(null);
    }
}