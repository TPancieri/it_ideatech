<?php

namespace App\Http\Controllers;

use App\Jobs\SendProcessSignatureInviteJob;
use App\Models\Cliente;
use App\Models\Processo;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProcessoWebController extends Controller
{
    public function index(): View
    {
        $processos = Processo::query()
            ->where('responsible_user_id', auth()->id())
            ->orderByDesc('id')
            ->paginate(20);

        return view('processos.index', compact('processos'));
    }

    public function create(): View
    {
        $clientes = Cliente::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('processos.create', compact('clientes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|max:255',
            'document' => 'nullable|file|mimes:pdf,jpeg,jpg,png,webp|max:10240',
            'signatarios' => 'nullable|array',
            'signatarios.*' => 'integer|exists:clientes,id',
        ]);

        $processo = null;

        DB::transaction(function () use ($request, $validated, &$processo): void {
            $processo = Processo::query()->create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'responsible_user_id' => $request->user()->id,
                'status' => 'pending',
            ]);

            $ids = array_values(array_unique(array_map('intval', $validated['signatarios'] ?? [])));
            foreach ($ids as $clienteId) {
                if (! Cliente::query()->whereKey($clienteId)->where('status', 'active')->exists()) {
                    continue;
                }
                $processo->signatarios()->syncWithoutDetaching([
                    $clienteId => ['sort_order' => 0],
                ]);
            }

            if ($request->hasFile('document')) {
                $disk = 'public';
                $path = $request->file('document')->store('processos/documents', $disk);
                $processo->document_path = $path;
                $processo->save();
            }
        });

        $processo->refresh();

        $clienteIds = $processo->signatarios()->pluck('clientes.id');
        $enviarConvites = ! $request->boolean('sem_convites');

        if ($enviarConvites && $clienteIds->isNotEmpty()) {
            $ttlHours = 72;
            foreach ($clienteIds as $clienteId) {
                Bus::dispatchSync(new SendProcessSignatureInviteJob($processo->id, (int) $clienteId, $ttlHours));
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
                meta: ['via' => 'web_processos_criacao'],
                request: $request,
            );
        }

        AuditLogger::log(
            acao: 'processo.criado',
            subject: $processo,
            actor: $request->user(),
            before: null,
            after: AuditLogger::processoSnapshot($processo),
            meta: ['via' => 'web_processos'],
            request: $request,
        );

        $message = 'Processo criado com sucesso.';
        if ($enviarConvites && $clienteIds->isNotEmpty()) {
            $message .= ' Convites enviados na hora para '.$clienteIds->count().' signatário(s) (tokens e e-mail na mesma requisição).';
        }

        return redirect()->route('processos.index')->with('success', $message);
    }
}
