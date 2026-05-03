<?php

namespace App\Http\Controllers;

use App\Jobs\SendProcessSignatureInviteJob;
use App\Models\Cliente;
use App\Models\Processo;
use App\Models\ProcessoAssinaturaToken;
use App\Services\AuditLogger;
use App\Services\ProcessSigningTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

final class FluxoAssinaturaWebController extends Controller
{
    private function assertResponsible(Processo $processo): void
    {
        abort_unless(
            (int) $processo->responsible_user_id === (int) auth()->id(),
            403
        );
    }

    public function index(): View
    {
        $processos = Processo::query()
            ->where('responsible_user_id', auth()->id())
            ->withCount('signatarios')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('fluxo-assinatura.index', compact('processos'));
    }

    public function show(Processo $processo): View
    {
        $this->assertResponsible($processo);
        $processo->load([
            'signatarios',
            'assinaturaTokens.cliente',
            'respostas.cliente',
        ]);

        return view('fluxo-assinatura.show', [
            'processo' => $processo,
            'modoFluxo' => $this->signingModeLabel($processo),
        ]);
    }

    private function signingModeLabel(Processo $processo): string
    {
        if ($processo->signatarios->isEmpty()) {
            return '— (sem signatários)';
        }

        $allZero = $processo->signatarios->every(fn (Cliente $c): bool => (int) $c->pivot->sort_order === 0);

        return $allZero ? 'Paralelo (todos com sort_order 0)' : 'Sequencial (ordem pelo sort_order)';
    }

    public function convitesStore(Request $request, Processo $processo): RedirectResponse
    {
        $this->assertResponsible($processo);

        $validator = Validator::make($request->all(), [
            'ttl_hours' => 'sometimes|integer|min:1|max:720',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $ttlHours = (int) ($validator->validated()['ttl_hours'] ?? 72);
        $clienteIds = $processo->signatarios()->pluck('clientes.id');

        if ($clienteIds->isEmpty()) {
            return back()->with('success', 'Não há signatários vinculados a este processo.');
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
            meta: ['via' => 'web_fluxo_assinatura'],
            request: $request,
        );

        return back()->with('success', 'Convites enfileirados para '.$clienteIds->count().' signatário(s) (TTL '.$ttlHours.' h). O worker da fila processará envio e tokens.');
    }

    public function linkGerar(
        Request $request,
        Processo $processo,
        ProcessSigningTokenService $tokens,
    ): RedirectResponse {
        $this->assertResponsible($processo);

        $data = $request->validate([
            'cliente_id' => 'required|integer|exists:clientes,id',
            'ttl_hours' => 'sometimes|integer|min:1|max:720',
        ]);

        if (! $processo->signatarios()->where('clientes.id', $data['cliente_id'])->exists()) {
            return back()->withErrors(['cliente_id' => 'Este signatário não está vinculado ao processo.']);
        }

        $ttlHours = (int) ($data['ttl_hours'] ?? 72);
        $cliente = Cliente::query()->findOrFail($data['cliente_id']);
        $issued = $tokens->issue($processo, $cliente, $ttlHours);
        $url = route('assinatura.show', ['token' => $issued['plain_token']]);

        AuditLogger::log(
            acao: 'processo.link_assinatura_gerado',
            subject: $processo,
            actor: $request->user(),
            before: null,
            after: [
                'cliente_id' => $cliente->id,
                'ttl_hours' => $ttlHours,
            ],
            meta: ['via' => 'web_fluxo_assinatura'],
            request: $request,
        );

        return back()
            ->with('assinatura_url_unica', $url)
            ->with('assinatura_link_para', $cliente->name)
            ->with('assinatura_link_nova_emissao', true);
    }

    public function revelarLink(
        Request $request,
        Processo $processo,
        ProcessoAssinaturaToken $assinaturaToken,
    ): RedirectResponse {
        $this->assertResponsible($processo);

        if ((int) $assinaturaToken->processo_id !== (int) $processo->id) {
            abort(404);
        }

        if ($assinaturaToken->consumed_at) {
            return back()->withErrors(['revelar' => 'Este token já foi utilizado; o link não é mais válido.']);
        }

        if ($assinaturaToken->expires_at->isPast()) {
            return back()->withErrors(['revelar' => 'Este token expirou.']);
        }

        $cipher = $assinaturaToken->invite_plain_ciphertext;
        if ($cipher === null || $cipher === '') {
            return back()->withErrors([
                'revelar' => 'Este convite foi emitido antes da recuperação de links. Use «Gerar link manual» para criar um novo token.',
            ]);
        }

        try {
            $plain = Crypt::decryptString($cipher);
        } catch (\Throwable) {
            return back()->withErrors([
                'revelar' => 'Não foi possível recuperar este link (dados corrompidos ou APP_KEY alterada).',
            ]);
        }

        $assinaturaToken->load('cliente');
        $url = route('assinatura.show', ['token' => $plain]);

        AuditLogger::log(
            acao: 'processo.link_assinatura_revelado',
            subject: $processo,
            actor: $request->user(),
            before: null,
            after: [
                'processo_assinatura_token_id' => $assinaturaToken->id,
                'cliente_id' => $assinaturaToken->cliente_id,
            ],
            meta: ['via' => 'web_fluxo_assinatura'],
            request: $request,
        );

        return back()
            ->with('assinatura_url_unica', $url)
            ->with('assinatura_link_para', $assinaturaToken->cliente?->name ?? 'Signatário')
            ->with('assinatura_link_revelado', true);
    }

    public function ordemUpdate(Request $request, Processo $processo): RedirectResponse
    {
        $this->assertResponsible($processo);

        $validator = Validator::make($request->all(), [
            'sort_order' => 'required|array',
            'sort_order.*' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $sortOrder = $validator->validated()['sort_order'];
        $currentIds = $processo->signatarios()->pluck('clientes.id')->sort()->values()->all();
        $submittedIds = collect(array_keys($sortOrder))->map(fn ($k): int => (int) $k)->sort()->values()->all();

        if ($currentIds !== $submittedIds) {
            return back()->withErrors(['sort_order' => 'Envie a ordem para todos os signatários atuais do processo.']);
        }

        $rows = [];
        foreach ($sortOrder as $cid => $so) {
            $rows[(int) $cid] = ['sort_order' => (int) $so];
        }

        $antes = $processo->signatarios()->get()->map(fn (Cliente $c): array => [
            'cliente_id' => $c->id,
            'sort_order' => (int) $c->pivot->sort_order,
        ])->values()->all();

        DB::transaction(function () use ($processo, $rows): void {
            $processo->signatarios()->sync($rows);
        });

        $processo->load('signatarios');
        $depois = $processo->signatarios->map(fn (Cliente $c): array => [
            'cliente_id' => $c->id,
            'sort_order' => (int) $c->pivot->sort_order,
        ])->values()->all();

        AuditLogger::log(
            acao: 'processo.signatarios_sincronizados',
            subject: $processo,
            actor: $request->user(),
            before: ['signatarios' => $antes],
            after: ['signatarios' => $depois],
            meta: ['via' => 'web_fluxo_ordem'],
            request: $request,
        );

        return back()->with('success', 'Ordem dos signatários atualizada.');
    }
}
