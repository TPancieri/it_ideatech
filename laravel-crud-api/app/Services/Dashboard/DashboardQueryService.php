<?php

namespace App\Services\Dashboard;

use App\Models\Cliente;
use App\Models\Processo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class DashboardQueryService
{
    /**
     * @return array<string,int|float|null>
     */
    public function summary(): array
    {
        $total = Processo::query()->count();

        $byStatus = Processo::query()
            ->select(['status', DB::raw('count(*) as c')])
            ->groupBy('status')
            ->pluck('c', 'status');

        $pending = (int) ($byStatus['pending'] ?? 0);
        $inApproval = (int) ($byStatus['in_approval'] ?? 0);
        $approved = (int) ($byStatus['approved'] ?? 0);
        $rejected = (int) ($byStatus['rejected'] ?? 0);
        $canceled = (int) ($byStatus['canceled'] ?? 0);

        $avgApprovalHours = $this->averageApprovalHours();

        return [
            'total_processos' => $total,
            'pending' => $pending,
            'in_approval' => $inApproval,
            'approved' => $approved,
            'rejected' => $rejected,
            'canceled' => $canceled,
            'avg_approval_hours' => $avgApprovalHours,
        ];
    }

    private function averageApprovalHours(): ?float
    {
        // Tempo médio entre criação do processo e momento em que virou "approved"
        // (aproximação objetiva e auditável via histórico).
        $driver = DB::connection()->getDriverName();

        $avgSeconds = match ($driver) {
            'pgsql' => Processo::query()
                ->join('processo_status_histories as h', function ($join): void {
                    $join->on('h.processo_id', '=', 'processos.id')
                        ->where('h.to_status', '=', 'approved');
                })
                ->selectRaw('avg(extract(epoch from (h.created_at - processos.created_at))) as avg_seconds')
                ->value('avg_seconds'),
            default => Processo::query()
                ->join('processo_status_histories as h', function ($join): void {
                    $join->on('h.processo_id', '=', 'processos.id')
                        ->where('h.to_status', '=', 'approved');
                })
                ->selectRaw('avg((julianday(h.created_at) - julianday(processos.created_at)) * 86400.0) as avg_seconds')
                ->value('avg_seconds'),
        };

        if ($avgSeconds === null) {
            return null;
        }

        return round(((float) $avgSeconds) / 3600, 2);
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public function overduePending(int $olderThanDays = 7): array
    {
        $threshold = Carbon::now()->subDays($olderThanDays);

        return Processo::query()
            ->where('status', 'pending')
            ->where('created_at', '<', $threshold)
            ->orderByDesc('id')
            ->get(['id', 'title', 'status', 'category', 'created_at'])
            ->map(fn (Processo $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'status' => $p->status,
                'category' => $p->category,
                'created_at' => $p->created_at,
                'days_pending' => $p->created_at ? $p->created_at->diffInDays(Carbon::now()) : null,
            ])
            ->all();
    }

    /**
     * @param  array<string,mixed>  $filters
     */
    public function filteredProcesses(array $filters): Builder
    {
        $q = Processo::query()->with(['responsibleUser:id,name,email']);

        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        if (! empty($filters['category'])) {
            $q->where('category', $filters['category']);
        }

        if (! empty($filters['from'])) {
            $q->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $q->whereDate('created_at', '<=', $filters['to']);
        }

        if (! empty($filters['signatario_id'])) {
            $signatarioId = (int) $filters['signatario_id'];
            $q->whereExists(function ($sub) use ($signatarioId): void {
                $sub->select(DB::raw(1))
                    ->from('cliente_processo as cp')
                    ->whereColumn('cp.processo_id', 'processos.id')
                    ->where('cp.cliente_id', $signatarioId);
            });
        }

        return $q->orderByDesc('id');
    }

    /**
     * @return array<int,string>
     */
    public function categories(): array
    {
        return Processo::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function signatariosOptions(): array
    {
        return Cliente::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (Cliente $c) => [
                'id' => $c->id,
                'label' => $c->name.' ('.$c->email.')',
            ])
            ->all();
    }
}
