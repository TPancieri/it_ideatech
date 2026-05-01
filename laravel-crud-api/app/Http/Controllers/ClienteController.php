<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller{

    public function index(){
        $cliente = Cliente::all();
        return response()-> json($cliente);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email'=> 'required|email|unique:clientes,email',
            'role'=> 'required|string',
            'sector'=>'required|string',
            'status'=>'sometimes|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        # $cliente = Cliente::create($validator->validated());
        $payload = $validator->validated();
        $payload['status'] = $payload['status'] ?? 'active';
        $cliente = Cliente::create($payload);


        return response()->json($cliente,201);
    }

    public function show (Cliente $cliente){
        return response()->json($cliente);
    }

    public function update(Request $request, Cliente $cliente){
        $validator = Validator::make($request->all(), [
        'name'=> 'required|string',
        'email'=>'required|email|unique:clientes,email,' . $cliente->id,
        'role'=> 'required|string',
        'sector'=>'required|string',
        'status'=>'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        $cliente->update($validator->validated());


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