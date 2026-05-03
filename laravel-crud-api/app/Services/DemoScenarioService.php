<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Processo;
use App\Models\ProcessoResposta;
use App\Models\ProcessoStatusHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Massa de dados marcada como demo (títulos `[Demo] ...`, e-mails `demo-seed-u{userId}-*`)
 * para relatórios, dashboard e filas sem misturar com dados “reais” do avaliador.
 */
final class DemoScenarioService
{
    public const TITLE_PREFIX = '[Demo] ';

    public static function demoClienteEmailPattern(int $userId): string
    {
        return 'demo-seed-u'.$userId.'-%@example.invalid';
    }

    /**
     * Remove processos demo e signatários demo do utilizador.
     */
    public function purgeForUser(User $user): int
    {
        $deletedProcessos = Processo::query()
            ->where('responsible_user_id', $user->id)
            ->where('title', 'like', self::TITLE_PREFIX.'%')
            ->delete();

        $deletedClientes = Cliente::query()
            ->where('email', 'like', 'demo-seed-u'.$user->id.'-%@example.invalid')
            ->delete();

        return (int) $deletedProcessos + (int) $deletedClientes;
    }

    /**
     * @return array{clientes: int, processos: int}
     */
    public function seed(User $user, bool $purgeFirst = true): array
    {
        if ($purgeFirst) {
            $this->purgeForUser($user);
        }

        $categories = ['Contratos', 'Compras', 'RH', 'Financeiro', 'Jurídico', 'TI'];
        $slugSuffixes = [
            'fornecedor X', 'adesão 2026', 'política interna', 'revisão trimestral',
            'projeto piloto', 'renovação anual', 'compliance', 'auditoria externa',
        ];

        return DB::transaction(function () use ($user, $categories, $slugSuffixes): array {
            $clientes = [];
            for ($i = 0; $i < 28; $i++) {
                $clientes[] = Cliente::query()->create([
                    'name' => fake()->name(),
                    'email' => 'demo-seed-u'.$user->id.'-'.$i.'@example.invalid',
                    'role' => fake()->randomElement(['Analista', 'Gerente', 'Diretor', 'Assistente']),
                    'sector' => fake()->randomElement(['Operações', 'Jurídico', 'Financeiro', 'TI']),
                    'status' => 'active',
                ]);
            }

            $clienteIds = array_map(static fn (Cliente $c): int => $c->id, $clientes);

            $statusPlan = array_merge(
                array_fill(0, 8, 'pending'),
                array_fill(0, 7, 'in_approval'),
                array_fill(0, 14, 'approved'),
                array_fill(0, 6, 'rejected'),
                array_fill(0, 5, 'canceled'),
            );
            shuffle($statusPlan);

            $processoCount = 0;

            foreach ($statusPlan as $idx => $targetStatus) {
                $title = self::TITLE_PREFIX.ucfirst(fake()->words(3, true)).' — '.$slugSuffixes[$idx % count($slugSuffixes)];
                $category = $categories[$idx % count($categories)];
                $createdAt = $this->randomDemoCreatedAt();

                $processo = Processo::query()->create([
                    'title' => $title,
                    'description' => 'Cenário gerado para testes (demo_seed). '.fake()->sentence(12),
                    'status' => 'pending',
                    'responsible_user_id' => $user->id,
                    'category' => $category,
                ]);
                $processo->timestamps = false;
                $processo->forceFill([
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])->saveQuietly();
                $processo->timestamps = true;

                $attachCount = random_int(1, min(4, count($clienteIds)));
                $picked = collect($clienteIds)->shuffle()->take($attachCount)->values();
                foreach ($picked as $order => $cid) {
                    $processo->signatarios()->attach($cid, ['sort_order' => $order]);
                }

                $this->applyTargetStatus($processo, $targetStatus, $picked->all());

                $processoCount++;
            }

            return [
                'clientes' => count($clientes),
                'processos' => $processoCount,
            ];
        });
    }

    /**
     * @param  list<int>  $signatarioIds
     */
    private function applyTargetStatus(Processo $processo, string $target, array $signatarioIds): void
    {
        $created = $processo->created_at?->copy() ?? Carbon::now()->copy();

        if ($target === 'pending') {
            $this->syncProcessoUpdatedAt($processo, $created);

            return;
        }

        if ($target === 'canceled') {
            $tCancel = $this->addRandomIntervalHours($created, 6, 400);
            $this->history($processo, 'pending', 'canceled', $tCancel, 'Cancelado antes da conclusão (demo).');
            $this->persistProcessoStatus($processo, 'canceled', $tCancel);

            return;
        }

        $tInApproval = $this->addRandomIntervalHours($created, 2, 96);
        $this->history($processo, 'pending', 'in_approval', $tInApproval, 'Entrada em aprovação (demo).');
        $this->persistProcessoStatus($processo, 'in_approval', $tInApproval);

        if ($target === 'in_approval') {
            return;
        }

        if ($target === 'approved') {
            $tResponse = $this->addRandomIntervalHours($tInApproval, 1.5, 168);
            $tFinal = $this->addRandomIntervalMinutes($tResponse, 4, 480);

            if ($signatarioIds !== []) {
                $cid = $signatarioIds[0];
                ProcessoResposta::query()->create([
                    'processo_id' => $processo->id,
                    'cliente_id' => $cid,
                    'tipo' => 'approved',
                    'justificativa' => null,
                    'ip' => '127.0.0.1',
                    'user_agent' => 'DemoScenarioService',
                    'created_at' => $tResponse,
                    'updated_at' => $tResponse,
                ]);
            }
            $this->history($processo, 'in_approval', 'approved', $tFinal, 'Aprovado (demo).');
            $this->persistProcessoStatus($processo, 'approved', $tFinal);

            return;
        }

        if ($target === 'rejected') {
            $tResponse = $this->addRandomIntervalHours($tInApproval, 1, 120);
            $tFinal = $this->addRandomIntervalMinutes($tResponse, 3, 240);

            if ($signatarioIds !== []) {
                $cid = $signatarioIds[0];
                ProcessoResposta::query()->create([
                    'processo_id' => $processo->id,
                    'cliente_id' => $cid,
                    'tipo' => 'rejected',
                    'justificativa' => 'Reprovação simulada em massa de dados.',
                    'ip' => '127.0.0.1',
                    'user_agent' => 'DemoScenarioService',
                    'created_at' => $tResponse,
                    'updated_at' => $tResponse,
                ]);
            }
            $this->history($processo, 'in_approval', 'rejected', $tFinal, 'Reprovado (demo).');
            $this->persistProcessoStatus($processo, 'rejected', $tFinal);
        }
    }

    private function persistProcessoStatus(Processo $processo, string $status, Carbon $updatedAt): void
    {
        $processo->timestamps = false;
        $processo->forceFill([
            'status' => $status,
            'updated_at' => $updatedAt,
        ])->saveQuietly();
        $processo->timestamps = true;
    }

    private function randomDemoCreatedAt(): Carbon
    {
        $daysAgo = random_int(28, 220);
        $at = Carbon::now()->subDays($daysAgo)->subMinutes(random_int(0, 1439));

        if (random_int(1, 100) <= 72) {
            $at->setTime(random_int(8, 17), random_int(0, 55), 0);
        }

        return $at;
    }

    private function addRandomIntervalHours(Carbon $from, float $minHours, float $maxHours): Carbon
    {
        $minM = max(1, (int) round($minHours * 60));
        $maxM = max($minM, (int) round($maxHours * 60));

        return $from->copy()->addMinutes(random_int($minM, $maxM));
    }

    private function addRandomIntervalMinutes(Carbon $from, int $minMinutes, int $maxMinutes): Carbon
    {
        $maxMinutes = max($minMinutes, $maxMinutes);

        return $from->copy()->addMinutes(random_int($minMinutes, $maxMinutes));
    }

    private function syncProcessoUpdatedAt(Processo $processo, Carbon $at): void
    {
        $processo->timestamps = false;
        $processo->forceFill(['updated_at' => $at])->saveQuietly();
        $processo->timestamps = true;
    }

    private function history(Processo $processo, ?string $from, string $to, Carbon $at, ?string $reason): void
    {
        ProcessoStatusHistory::query()->create([
            'processo_id' => $processo->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_type' => null,
            'actor_id' => null,
            'reason' => $reason,
            'meta' => ['demo_seed' => true],
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
}
