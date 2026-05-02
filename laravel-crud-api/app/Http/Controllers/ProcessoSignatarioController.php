<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Processo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProcessoSignatarioController extends Controller
{
    public function index(Processo $processo)
    {
        $signatarios = $processo->signatarios()->get();

        return response()->json($signatarios);
    }

    public function store(Request $request, Processo $processo)
    {
        $validator = Validator::make($request->all(), [
            'cliente_id' => 'required|integer|exists:clientes,id',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $sortOrder = $data['sort_order'] ?? 0;

        if ($processo->signatarios()->where('clientes.id', $data['cliente_id'])->exists()) {
            return response()->json([
                'message' => 'Signatário já associado a este processo.',
            ], 422);
        }

        $processo->signatarios()->attach($data['cliente_id'], [
            'sort_order' => $sortOrder,
        ]);

        return response()->json($processo->signatarios()->get(), 201);
    }

    public function sync(Request $request, Processo $processo)
    {
        $validator = Validator::make($request->all(), [
            'signatarios' => 'required|array|min:1',
            'signatarios.*.cliente_id' => 'required|integer|distinct|exists:clientes,id',
            'signatarios.*.sort_order' => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $rows = collect($validator->validated()['signatarios'])
            ->mapWithKeys(function (array $row): array {
                $id = (int) $row['cliente_id'];
                $sort = array_key_exists('sort_order', $row) ? (int) $row['sort_order'] : 0;

                return [
                    $id => ['sort_order' => $sort],
                ];
            })
            ->all();

        DB::transaction(function () use ($processo, $rows): void {
            $processo->signatarios()->sync($rows);
        });

        return response()->json($processo->signatarios()->get(), 200);
    }

    public function destroy(Processo $processo, Cliente $cliente)
    {
        $processo->signatarios()->detach($cliente->id);

        return response()->json(null, 204);
    }
}
