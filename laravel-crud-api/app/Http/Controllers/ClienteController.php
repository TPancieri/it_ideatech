<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Services\AuditLogger;
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

        $payload = $validator->validated();
        $payload['status'] = $payload['status'] ?? 'active';

        $cliente = Cliente::create($payload);

        AuditLogger::log(
            acao: 'cliente.criado',
            subject: $cliente,
            actor: $request->user(),
            before: null,
            after: AuditLogger::clienteSnapshot($cliente),
            meta: ['via' => 'api'],
            request: $request,
        );

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

        $before = AuditLogger::clienteSnapshot($cliente);

        $cliente->update($validator->validated());
        $cliente->refresh();

        AuditLogger::log(
            acao: 'cliente.atualizado',
            subject: $cliente,
            actor: $request->user(),
            before: $before,
            after: AuditLogger::clienteSnapshot($cliente),
            meta: ['via' => 'api'],
            request: $request,
        );

        return response()->json($cliente,200);
    }

    public function destroy(Request $request, Cliente $cliente){
        $before = AuditLogger::clienteSnapshot($cliente);

        $cliente->status = 'inactive';
        $cliente->save();
        $cliente->refresh();

        AuditLogger::log(
            acao: 'cliente.inativado',
            subject: $cliente,
            actor: $request->user(),
            before: $before,
            after: AuditLogger::clienteSnapshot($cliente),
            meta: ['via' => 'api'],
            request: $request,
        );

        return response()->json($cliente, 200);
    }
}