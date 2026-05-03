<?php

namespace App\Http\Controllers;

use App\Jobs\SendProcessSignatureInviteJob;
use App\Models\Processo;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProcessInvitationController extends Controller
{
    public function enviar(Request $request, Processo $processo)
    {
        $validator = Validator::make($request->all(), [
            'ttl_hours' => 'sometimes|integer|min:1|max:720',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ttlHours = (int) ($validator->validated()['ttl_hours'] ?? 72);

        $clienteIds = $processo->signatarios()->pluck('clientes.id');

        if ($clienteIds->isEmpty()) {
            return response()->json([
                'message' => 'Processo não possui signatários associados.',
            ], 422);
        }

        foreach ($clienteIds as $clienteId) {
            SendProcessSignatureInviteJob::dispatch($processo->id, (int) $clienteId, $ttlHours);
        }

        AuditLogger::log(
            acao: 'processo.convites_enfileirados',
            subject: $processo,
            actor: $request->user(),
            before: null,
            after: [
                'ttl_hours' => $ttlHours,
                'signatario_ids' => $clienteIds->values()->all(),
                'jobs' => $clienteIds->count(),
            ],
            meta: ['via' => 'api_convites'],
            request: $request,
        );

        return response()->json([
            'message' => 'Convites enfileirados.',
            'signatarios' => $clienteIds->count(),
        ], 202);
    }
}
