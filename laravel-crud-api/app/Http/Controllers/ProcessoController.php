<?php

namespace App\Http\Controllers;

use App\Models\Processo;
use App\Services\AuditLogger;
use App\Services\ProcessoStatusPolicy;
use App\Services\StatusTransitionLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
            'description' => 'required|string',
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
        // New processes always start as pending; lifecycle changes happen via update rules.
        $payload['status'] = 'pending';

        $processo = Processo::create($payload);

        AuditLogger::log(
            acao: 'processo.criado',
            subject: $processo,
            actor: $request->user(),
            before: null,
            after: AuditLogger::processoSnapshot($processo),
            meta: ['via' => 'api'],
            request: $request,
        );

        return response()->json($processo, 201);
    }

    public function show(Processo $processo)
    {
        return response()->json($processo);
    }

    public function update(Request $request, Processo $processo)
    {
        $beforeStatus = $processo->status;
        $beforeSnap = AuditLogger::processoSnapshot($processo);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
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

        $data = $validator->validated();
        $nextStatus = $data['status'] ?? $processo->status;

        $message = ProcessoStatusPolicy::validateTransition($processo->status, $nextStatus);
        if ($message) {
            return response()->json([
                'message' => $message,
                'errors' => [
                    'status' => [$message],
                ],
            ], 422);
        }

        $data['status'] = $nextStatus;

        $processo->update($data);

        $processo->refresh();

        if ($beforeStatus !== $processo->status) {
            StatusTransitionLogger::record(
                processo: $processo,
                from: $beforeStatus,
                to: $processo->status,
                actor: $request->user(),
                reason: null,
                meta: [
                    'via' => 'api_update',
                ],
            );

            AuditLogger::log(
                acao: 'processo.status_atualizado',
                subject: $processo,
                actor: $request->user(),
                before: ['status' => $beforeStatus],
                after: ['status' => $processo->status],
                meta: [
                    'via' => 'api_update',
                ],
                request: $request,
            );
        } else {
            $afterSnap = AuditLogger::processoSnapshot($processo);
            if ($beforeSnap != $afterSnap) {
                AuditLogger::log(
                    acao: 'processo.atualizado',
                    subject: $processo,
                    actor: $request->user(),
                    before: $beforeSnap,
                    after: $afterSnap,
                    meta: ['via' => 'api_update'],
                    request: $request,
                );
            }
        }

        return response()->json($processo, 200);
    }

    public function destroy(Request $request, Processo $processo)
    {
        AuditLogger::log(
            acao: 'processo.excluido',
            subject: $processo,
            actor: $request->user(),
            before: AuditLogger::processoSnapshot($processo),
            after: null,
            meta: ['via' => 'api_destroy'],
            request: $request,
        );

        $processo->delete();
        return response()->json(null, 204);
    }

    public function uploadDocument(Request $request, Processo $processo)
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:pdf,jpeg,jpg,png,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $disk = 'public';

        $beforePath = $processo->document_path;

        if ($processo->document_path) {
            Storage::disk($disk)->delete($processo->document_path);
        }

        $path = $request->file('document')->store('processos/documents', $disk);

        $processo->document_path = $path;
        $processo->save();

        AuditLogger::log(
            acao: 'processo.documento_atualizado',
            subject: $processo,
            actor: $request->user(),
            before: ['document_path' => $beforePath],
            after: ['document_path' => $path],
            meta: ['via' => 'api_upload'],
            request: $request,
        );

        return response()->json($processo, 200);
    }

    public function showDocument(Processo $processo): BinaryFileResponse|JsonResponse
    {
        if (! $processo->document_path) {
            return response()->json([
                'message' => 'No document uploaded for this processo.',
            ], 404);
        }

        $disk = 'public';

        if (! Storage::disk($disk)->exists($processo->document_path)) {
            return response()->json([
                'message' => 'Document file missing on disk.',
            ], 404);
        }

        $absolutePath = Storage::disk($disk)->path($processo->document_path);

        $mime = Storage::disk($disk)->mimeType($processo->document_path) ?: 'application/octet-stream';
        $filename = basename($processo->document_path);

        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}

