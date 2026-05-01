<?php

namespace App\Http\Controllers;

use App\Models\Processo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProcessoController extends Controller
{

    public function index()
    {
        $processos = Processo::query()->orderByDesc('id')->get();
        return response()->json($processos);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:pending,in_approval,approved,rejected,canceled',
            'responsible_user_id' => 'required|integer|exists:users,id',
            'category' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();

        $payload['status'] = $payload['status'] ?? 'pending';

        $processo = Processo::create($payload);

        return response()->json($processo, 201);
    }


    public function show(Processo $processo)
    {
        return response()->json($processo);
    }


    public function update(Request $request, Processo $processo)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|in:pending,in_approval,approved,rejected,canceled',
            'responsible_user_id' => 'required|integer|exists:users,id',
            'category' => 'required|string|max:255',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }


        $processo->update($validator->validated());

        return response()->json($processo, 200);
    }

    
    public function destroy(Processo $processo)
    {
        $processo->delete();
        return response()->json(null, 204);
    }
}