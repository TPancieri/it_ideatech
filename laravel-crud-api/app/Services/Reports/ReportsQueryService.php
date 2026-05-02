<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class ReportsQueryService
{
    /**
     * @return array<int, array{status:string,count:int,percent:float}>
     */
    public function processesByStatus(): array
    {
        $rows = DB::table('processos')
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $total = (int) $rows->sum('c');

        return $rows->map(function ($row) use ($total): array {
            $count = (int) $row->c;
            $percent = $total > 0 ? round(($count / $total) * 100, 2) : 0.0;

            return [
                'status' => (string) $row->status,
                'count' => $count,
                'percent' => $percent,
            ];
        })->values()->all();
    }

    /**
     * Tempo médio de resposta por signatário (primeira resposta por processo).
     *
     * @return array<int, array{cliente_id:int,name:?string,email:?string,approvals:int,rejections:int,avg_response_hours:?float}>
     */
    public function productivityBySignatario(?Carbon $from = null, ?Carbon $to = null): array
    {
        $driver = DB::connection()->getDriverName();

        $counts = DB::table('processo_respostas as r')
            ->when($from, fn ($q) => $q->whereDate('r.created_at', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate('r.created_at', '<=', $to->toDateString()))
            ->selectRaw('r.cliente_id')
            ->selectRaw("sum(case when r.tipo = 'approved' then 1 else 0 end) as approvals")
            ->selectRaw("sum(case when r.tipo = 'rejected' then 1 else 0 end) as rejections")
            ->groupBy('r.cliente_id');

        $firstResponses = DB::table('processo_respostas as r')
            ->join('processos as p', 'p.id', '=', 'r.processo_id')
            ->when($from, fn ($q) => $q->whereDate('r.created_at', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate('r.created_at', '<=', $to->toDateString()))
            ->selectRaw('r.cliente_id, r.processo_id, min(r.created_at) as first_at')
            ->groupBy(['r.cliente_id', 'r.processo_id']);

        $avgExpr = match ($driver) {
            'pgsql' => 'avg(extract(epoch from (fr.first_at - p.created_at)))',
            default => 'avg((julianday(fr.first_at) - julianday(p.created_at)) * 86400.0)',
        };

        $avg = DB::query()
            ->fromSub($firstResponses, 'fr')
            ->join('processos as p', 'p.id', '=', 'fr.processo_id')
            ->selectRaw('fr.cliente_id')
            ->selectRaw($avgExpr.' as avg_seconds')
            ->groupBy('fr.cliente_id');

        $rows = DB::query()
            ->fromSub($counts, 'c')
            ->leftJoinSub($avg, 'a', 'a.cliente_id', '=', 'c.cliente_id')
            ->leftJoin('clientes as cl', 'cl.id', '=', 'c.cliente_id')
            ->selectRaw('c.cliente_id')
            ->selectRaw('max(cl.name) as name')
            ->selectRaw('max(cl.email) as email')
            ->selectRaw('max(c.approvals) as approvals')
            ->selectRaw('max(c.rejections) as rejections')
            ->selectRaw('max(a.avg_seconds) as avg_seconds')
            ->groupBy('c.cliente_id')
            ->orderByDesc(DB::raw('max(c.approvals)'))
            ->get();

        return $rows->map(function ($row): array {
            $avgSeconds = $row->avg_seconds !== null ? (float) $row->avg_seconds : null;

            return [
                'cliente_id' => (int) $row->cliente_id,
                'name' => $row->name,
                'email' => $row->email,
                'approvals' => (int) $row->approvals,
                'rejections' => (int) $row->rejections,
                'avg_response_hours' => $avgSeconds === null ? null : round($avgSeconds / 3600, 2),
            ];
        })->values()->all();
    }

    /**
     * @return array<int, array{period:string,created:int,concluded:int}>
     */
    public function processesByPeriod(string $grain, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $driver = DB::connection()->getDriverName();

        $periodSql = match ($grain) {
            'week' => match ($driver) {
                'pgsql' => "to_char(date_trunc('week', created_at), 'IYYY-\"W\"IW')",
                default => "strftime('%Y-W%W', created_at)",
            },
            'month' => match ($driver) {
                'pgsql' => "to_char(date_trunc('month', created_at), 'YYYY-MM')",
                default => "strftime('%Y-%m', created_at)",
            },
            default => match ($driver) {
                'pgsql' => "to_char(date_trunc('day', created_at), 'YYYY-MM-DD')",
                default => "strftime('%Y-%m-%d', created_at)",
            },
        };

        $created = DB::table('processos')
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to->toDateString()))
            ->selectRaw("$periodSql as period")
            ->selectRaw('count(*) as c')
            ->groupBy('period')
            ->orderBy('period');

        $concludedExpr = match ($driver) {
            'pgsql' => "case
                when status = 'approved' then (
                    select min(h.created_at)
                    from processo_status_histories h
                    where h.processo_id = processos.id and h.to_status = 'approved'
                )
                when status = 'rejected' then (
                    select min(h.created_at)
                    from processo_status_histories h
                    where h.processo_id = processos.id and h.to_status = 'rejected'
                )
                else null
            end",
            default => "case
                when status = 'approved' then (
                    select min(h.created_at)
                    from processo_status_histories h
                    where h.processo_id = processos.id and h.to_status = 'approved'
                )
                when status = 'rejected' then (
                    select min(h.created_at)
                    from processo_status_histories h
                    where h.processo_id = processos.id and h.to_status = 'rejected'
                )
                else null
            end",
        };

        $concluded = DB::table('processos')
            ->whereIn('status', ['approved', 'rejected'])
            ->whereRaw("$concludedExpr is not null")
            ->selectRaw("$concludedExpr as concluded_at")
            ->when($from, fn ($q) => $q->whereRaw('date(concluded_at) >= ?', [$from->toDateString()]))
            ->when($to, fn ($q) => $q->whereRaw('date(concluded_at) <= ?', [$to->toDateString()]));

        $concludedGrouped = DB::query()
            ->fromSub($concluded, 'x')
            ->selectRaw(match ($grain) {
                'week' => match ($driver) {
                    'pgsql' => "to_char(date_trunc('week', x.concluded_at), 'IYYY-\"W\"IW') as period",
                    default => "strftime('%Y-W%W', x.concluded_at) as period",
                },
                'month' => match ($driver) {
                    'pgsql' => "to_char(date_trunc('month', x.concluded_at), 'YYYY-MM') as period",
                    default => "strftime('%Y-%m', x.concluded_at) as period",
                },
                default => match ($driver) {
                    'pgsql' => "to_char(date_trunc('day', x.concluded_at), 'YYYY-MM-DD') as period",
                    default => "strftime('%Y-%m-%d', x.concluded_at) as period",
                },
            })
            ->selectRaw('count(*) as c')
            ->groupBy('period')
            ->orderBy('period');

        $createdRows = $created->get()->keyBy('period');
        $concludedRows = $concludedGrouped->get()->keyBy('period');

        $periods = collect($createdRows->keys()->merge($concludedRows->keys()))
            ->unique()
            ->sort()
            ->values();

        return $periods->map(function ($period) use ($createdRows, $concludedRows): array {
            $p = (string) $period;

            return [
                'period' => $p,
                'created' => (int) ($createdRows[$p]->c ?? 0),
                'concluded' => (int) ($concludedRows[$p]->c ?? 0),
            ];
        })->all();
    }

    /**
     * @return array<int, array{processo_id:int,title:string,categoria:string,signatario:string,email:?string,rejected_at:?string,justificativa:?string}>
     */
    public function rejectionsReport(?Carbon $from = null, ?Carbon $to = null): array
    {
        $q = DB::table('processo_respostas as r')
            ->join('processos as p', 'p.id', '=', 'r.processo_id')
            ->join('clientes as c', 'c.id', '=', 'r.cliente_id')
            ->where('r.tipo', '=', 'rejected')
            ->when($from, fn ($qq) => $qq->whereDate('r.created_at', '>=', $from->toDateString()))
            ->when($to, fn ($qq) => $qq->whereDate('r.created_at', '<=', $to->toDateString()))
            ->orderByDesc('r.id')
            ->select([
                'p.id as processo_id',
                'p.title as title',
                'p.category as categoria',
                'c.name as signatario',
                'c.email as email',
                'r.created_at as rejected_at',
                'r.justificativa as justificativa',
            ]);

        return collect($q->get())->map(function ($row): array {
            return [
                'processo_id' => (int) $row->processo_id,
                'title' => (string) $row->title,
                'categoria' => (string) $row->categoria,
                'signatario' => (string) $row->signatario,
                'email' => $row->email,
                'rejected_at' => $row->rejected_at,
                'justificativa' => $row->justificativa,
            ];
        })->all();
    }
}
