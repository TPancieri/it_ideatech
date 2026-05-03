<?php

namespace App\Http\Controllers;

use App\Jobs\SendProcessSignatureInviteJob;
use App\Models\Cliente;
use App\Models\Processo;
use App\Services\AuditLogger;
use App\Services\ProcessoStatusPolicy;
use App\Services\StatusTransitionLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
            $message .= ' Convites enfileirados para '.$clienteIds->count().' signatário(s). Com `QUEUE_CONNECTION=database` ou `redis`, rode `php artisan queue:work` para gerar tokens e enviar e-mails.';
        }

        return redirect()->route('processos.index')->with('success', $message);
    }

    public function edit(Processo $processo): View
    {
        $this->authorize('update', $processo);

        $clientes = Cliente::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $statusOptions = ProcessoStatusPolicy::statusesAllowedForForm($processo->status);

        return view('processos.edit', compact('processo', 'clientes', 'statusOptions'));
    }

    public function update(Request $request, Processo $processo): RedirectResponse
    {
        $this->authorize('update', $processo);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|max:255',
            'status' => ['required', 'string', Rule::in(ProcessoStatusPolicy::statusesAllowedForForm($processo->status))],
            'document' => 'nullable|file|mimes:pdf,jpeg,jpg,png,webp|max:10240',
            'signatarios' => 'nullable|array',
            'signatarios.*' => 'integer|exists:clientes,id',
        ]);

        $transitionError = ProcessoStatusPolicy::validateTransition($processo->status, $validated['status']);
        if ($transitionError) {
            return back()->withErrors(['status' => $transitionError])->withInput();
        }

        $beforeStatus = $processo->status;
        $beforeSnap = AuditLogger::processoSnapshot($processo);

        DB::transaction(function () use ($request, $validated, $processo): void {
            $processo->title = $validated['title'];
            $processo->description = $validated['description'];
            $processo->category = $validated['category'];
            $processo->status = $validated['status'];
            $processo->save();

            $ids = array_values(array_unique(array_map('intval', $validated['signatarios'] ?? [])));
            $rows = [];
            foreach ($ids as $clienteId) {
                if (! Cliente::query()->whereKey($clienteId)->where('status', 'active')->exists()) {
                    continue;
                }
                $rows[$clienteId] = ['sort_order' => 0];
            }
            $processo->signatarios()->sync($rows);

            if ($request->hasFile('document')) {
                $disk = 'public';
                if ($processo->document_path) {
                    Storage::disk($disk)->delete($processo->document_path);
                }
                $path = $request->file('document')->store('processos/documents', $disk);
                $processo->document_path = $path;
                $processo->save();
            }
        });

        $processo->refresh();

        $afterSnap = AuditLogger::processoSnapshot($processo);

        if ($beforeStatus !== $processo->status) {
            StatusTransitionLogger::record(
                processo: $processo,
                from: $beforeStatus,
                to: $processo->status,
                actor: $request->user(),
                reason: null,
                meta: ['via' => 'web_processos_update'],
            );

            AuditLogger::log(
                acao: 'processo.status_atualizado',
                subject: $processo,
                actor: $request->user(),
                before: ['status' => $beforeStatus],
                after: ['status' => $processo->status],
                meta: ['via' => 'web_processos_update'],
                request: $request,
            );
        } elseif ($beforeSnap !== $afterSnap) {
            AuditLogger::log(
                acao: 'processo.atualizado',
                subject: $processo,
                actor: $request->user(),
                before: $beforeSnap,
                after: $afterSnap,
                meta: ['via' => 'web_processos_update'],
                request: $request,
            );
        }

        return redirect()->route('processos.index')->with('success', 'Processo atualizado.');
    }

    public function destroy(Request $request, Processo $processo): RedirectResponse
    {
        $this->authorize('delete', $processo);

        AuditLogger::log(
            acao: 'processo.excluido',
            subject: $processo,
            actor: $request->user(),
            before: AuditLogger::processoSnapshot($processo),
            after: null,
            meta: ['via' => 'web_processos'],
            request: $request,
        );

        if ($processo->document_path) {
            Storage::disk('public')->delete($processo->document_path);
        }

        $processo->delete();

        return redirect()->route('processos.index')->with('success', 'Processo excluído.');
    }
}
