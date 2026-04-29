<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ControllerCliente extends Controller
{
    public function index()
    {// retornando um .json
    $clientes = Cliente::all();
    return response()->json($clientes);}

    public function store(Request $request)
    {
        $validatedData = $request ->validate([
            'nome' => 'required|string',
            'email' => 'required|string' 
        ]);

        $clientes = Cliente::create($request->all());
        return response()->json($clientes);
    }

    public function show(Cliente $clientes)
    {
        return response()->json($clientes);
    }

    public function update(Request $request, Cliente $clientes)
    {
        $validatedData = $request ->validate([
            'nome' => 'required|string',
            'email' => 'required|string' 
        ]);

        $clientes ->update($validatedData);

        return response()->json($clientes);
    }

    public function destroy (Cliente $clientes)
    {
        $clientes->delete();
        return response()->json(null);
    }
}