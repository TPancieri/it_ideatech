<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Processo;
use App\Models\ProcessoResposta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ApprovalFlowService
{
    public function __construct(
        private readonly ProcessSigningTokenService $tokens,
    ) {
    }

    public function approve(string $plainToken, Request $request): Processo
    {
        $token = $this->tokens->findValid($plainToken);
        if (! $token) {
            abort(410, 'Token inválido ou expirado.');
        }

        return DB::transaction(function () use ($token, $request): Processo {
            /** @var Processo $processo */
            $processo = Processo::query()->lockForUpdate()->findOrFail($token->processo_id);
            /** @var Cliente $cliente */
            $cliente = Cliente::query()->findOrFail($token->cliente_id);

            $this->assertSignatarioAtivo($processo, $cliente);
            $this->assertCanRespond($processo, $cliente, 'approved');

            ProcessoResposta::query()->create([
                'processo_id' => $processo->id,
                'cliente_id' => $cliente->id,
                'tipo' => 'approved',
                'justificativa' => null,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            AuditLogger::log(
                acao: 'processo.aprovacao',
                subject: $processo,
                actor: $cliente,
                meta: [
                    'cliente_id' => $cliente->id,
                ],
                request: $request,
            );

            if ($processo->status === 'pending') {
                $this->transition($processo, 'pending', 'in_approval', $cliente, null, [
                    'via' => 'assinatura',
                ]);
            }

            $next = $this->computeNextProcessStatusAfterApprovalResponse($processo);
            if ($next !== $processo->status) {
                $this->transition($processo, $processo->status, $next, $cliente, null, [
                    'via' => 'assinatura',
                ]);
            }

            $this->tokens->consume($token);

            return $processo->refresh();
        });
    }

    public function reject(string $plainToken, string $justificativa, Request $request): Processo
    {
        $token = $this->tokens->findValid($plainToken);
        if (! $token) {
            abort(410, 'Token inválido ou expirado.');
        }

        return DB::transaction(function () use ($token, $request, $justificativa): Processo {
            /** @var Processo $processo */
            $processo = Processo::query()->lockForUpdate()->findOrFail($token->processo_id);
            /** @var Cliente $cliente */
            $cliente = Cliente::query()->findOrFail($token->cliente_id);

            $this->assertSignatarioAtivo($processo, $cliente);
            $this->assertCanRespond($processo, $cliente, 'rejected');

            ProcessoResposta::query()->create([
                'processo_id' => $processo->id,
                'cliente_id' => $cliente->id,
                'tipo' => 'rejected',
                'justificativa' => $justificativa,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            AuditLogger::log(
                acao: 'processo.reprovacao',
                subject: $processo,
                actor: $cliente,
                meta: [
                    'cliente_id' => $cliente->id,
                ],
                request: $request,
            );

            if ($processo->status === 'pending') {
                $this->transition($processo, 'pending', 'in_approval', $cliente, null, [
                    'via' => 'assinatura',
                ]);
            }

            $this->transition($processo, $processo->status, 'rejected', $cliente, $justificativa, [
                'via' => 'assinatura',
            ]);

            $this->tokens->consume($token);

            return $processo->refresh();
        });
    }

    private function assertSignatarioAtivo(Processo $processo, Cliente $cliente): void
    {
        $attached = $processo->signatarios()->where('clientes.id', $cliente->id)->exists();
        if (! $attached) {
            abort(403, 'Signatário não pertence a este processo.');
        }

        if (($cliente->status ?? null) === 'inactive') {
            abort(403, 'Signatário inativo.');
        }
    }

    private function assertCanRespond(Processo $processo, Cliente $cliente, string $tipo): void
    {
        if (in_array($processo->status, ['approved', 'rejected', 'canceled'], true)) {
            abort(409, 'Processo já finalizado.');
        }

        $already = ProcessoResposta::query()
            ->where('processo_id', $processo->id)
            ->where('cliente_id', $cliente->id)
            ->exists();

        if ($already) {
            abort(409, 'Signatário já respondeu.');
        }

        // Regra de sequencia / paralelo sao forcadas na etapa de computacao para aprovacao
        // no caso de rejeicao, permite ser imediato se e sua vez (sequencia) ou sempre (paralelo)
        $mode = $this->signingMode($processo);

        if ($mode === 'parallel') {
            return;
        }

        if (! $this->isTurnForCliente($processo, $cliente)) {
            abort(409, 'Ainda não é a vez deste signatário.');
        }
    }

    private function signingMode(Processo $processo): string
    {
        $orders = $processo->signatarios()->pluck('cliente_processo.sort_order');

        return $orders->every(fn ($o) => (int) $o === 0) ? 'parallel' : 'sequential';
    }

    private function isTurnForCliente(Processo $processo, Cliente $cliente): bool
    {
        $pivot = $processo->signatarios()
            ->where('clientes.id', $cliente->id)
            ->first()
            ?->pivot;

        if (! $pivot) {
            return false;
        }

        $step = (int) $pivot->sort_order;
        if ($step <= 0) {
            return true;
        }

        $distinctSteps = $processo->signatarios()
            ->pluck('cliente_processo.sort_order')
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->sort()
            ->values();

        $currentStep = $this->currentSequentialStep($processo, $distinctSteps);

        return $currentStep === $step;
    }

    /**
     * @param  \Illuminate\Support\Collection<int,int>  $distinctSteps
     */
    private function currentSequentialStep(Processo $processo, $distinctSteps): int
    {
        foreach ($distinctSteps as $step) {
            $clienteIdsInStep = $processo->signatarios()
                ->wherePivot('sort_order', $step)
                ->pluck('clientes.id');

            $required = $clienteIdsInStep->count();
            $approvedCount = ProcessoResposta::query()
                ->where('processo_id', $processo->id)
                ->whereIn('cliente_id', $clienteIdsInStep->all())
                ->where('tipo', 'approved')
                ->count();

            if ($approvedCount < $required) {
                return (int) $step;
            }
        }

        return $distinctSteps->last() ?? 1;
    }

    private function computeNextProcessStatusAfterApprovalResponse(Processo $processo): string
    {
        $mode = $this->signingMode($processo);

        if ($mode === 'parallel') {
            $total = $processo->signatarios()->count();
            $approved = ProcessoResposta::query()
                ->where('processo_id', $processo->id)
                ->where('tipo', 'approved')
                ->count();

            return $approved >= $total ? 'approved' : 'in_approval';
        }

        $distinctSteps = $processo->signatarios()
            ->pluck('cliente_processo.sort_order')
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->sort()
            ->values();

        foreach ($distinctSteps as $step) {
            $clienteIdsInStep = $processo->signatarios()
                ->wherePivot('sort_order', $step)
                ->pluck('clientes.id');

            $required = $clienteIdsInStep->count();
            $approvedCount = ProcessoResposta::query()
                ->where('processo_id', $processo->id)
                ->whereIn('cliente_id', $clienteIdsInStep->all())
                ->where('tipo', 'approved')
                ->count();

            if ($approvedCount < $required) {
                return 'in_approval';
            }
        }

        return 'approved';
    }

    private function transition(Processo $processo, string $from, string $to, ?Cliente $actor, ?string $reason, array $meta): void
    {
        $msg = ProcessoStatusPolicy::validateTransition($from, $to);
        if ($msg) {
            abort(409, $msg);
        }

        $processo->status = $to;
        $processo->save();

        StatusTransitionLogger::record($processo, $from, $to, $actor, $reason, $meta);
    }
}
