<?php

namespace App\Services\Analytics;

use App\Services\Dashboard\DashboardQueryService;
use App\Services\Reports\ReportsQueryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class AnalyticsQueryService
{
    public function __construct(
        private readonly DashboardQueryService $dashboard,
        private readonly ReportsQueryService $reports,
    ) {
    }

    /**
     * Perguntas mínimas (Req. 6):
     * - tempo médio de aprovação
     * - signatários que mais aprovam/reprovam
     * - categoria com maior volume
     * - status com maior volume atual
     * - processos criados por período
     *
     * @return array<string,mixed>
     */
    public function snapshot(array $options = []): array
    {
        $grain = $options['grain'] ?? 'day';
        if (! in_array($grain, ['day', 'week', 'month'], true)) {
            $grain = 'day';
        }

        $from = isset($options['from']) && $options['from'] ? Carbon::parse($options['from']) : null;
        $to = isset($options['to']) && $options['to'] ? Carbon::parse($options['to']) : null;

        $summary = $this->dashboard->summary();

        $topSignatarios = $this->reports->productivityBySignatario($from, $to);
        $topSignatarios = array_slice($topSignatarios, 0, 10);

        $categoryVolume = $this->categoryVolume();
        $statusVolume = $this->statusVolume();

        $createdByPeriod = $this->reports->processesByPeriod($grain, $from, $to);

        return [
            'avg_approval_hours' => $summary['avg_approval_hours'],
            'top_signatarios' => $topSignatarios,
            'category_volume' => $categoryVolume,
            'status_volume' => $statusVolume,
            'created_by_period' => $createdByPeriod,
            'options' => [
                'grain' => $grain,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
        ];
    }

    /**
     * @return array<int, array{category:string,count:int}>
     */
    public function categoryVolume(): array
    {
        $rows = DB::table('processos')
            ->selectRaw('category, count(*) as c')
            ->groupBy('category')
            ->orderByDesc('c')
            ->get();

        return $rows->map(fn ($r) => [
            'category' => (string) $r->category,
            'count' => (int) $r->c,
        ])->values()->all();
    }

    /**
     * @return array<int, array{status:string,count:int}>
     */
    public function statusVolume(): array
    {
        $rows = DB::table('processos')
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->orderByDesc('c')
            ->get();

        return $rows->map(fn ($r) => [
            'status' => (string) $r->status,
            'count' => (int) $r->c,
        ])->values()->all();
    }
}

