<?php

namespace App\Http\Controllers;

use App\Services\Reports\ReportsQueryService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function index(): View
    {
        return view('reports.index');
    }

    public function status(Request $request, ReportsQueryService $reports): View
    {
        $rows = $reports->processesByStatus((int) $request->user()->id);

        return view('reports.status', [
            'rows' => $rows,
        ]);
    }

    public function statusCsv(Request $request, ReportsQueryService $reports): StreamedResponse
    {
        $rows = $reports->processesByStatus((int) $request->user()->id);

        return $this->csvStream('relatorio_processos_por_status.csv', ['status', 'count', 'percent'], array_map(fn ($r) => [
            $r['status'],
            (string) $r['count'],
            (string) $r['percent'],
        ], $rows));
    }

    public function productivity(Request $request, ReportsQueryService $reports): View
    {
        $from = $request->date('from');
        $to = $request->date('to');

        $rows = $reports->productivityBySignatario((int) $request->user()->id, $from, $to);

        return view('reports.productivity', [
            'rows' => $rows,
            'from' => $from?->format('Y-m-d'),
            'to' => $to?->format('Y-m-d'),
        ]);
    }

    public function productivityCsv(Request $request, ReportsQueryService $reports): StreamedResponse
    {
        $from = $request->date('from');
        $to = $request->date('to');

        $rows = $reports->productivityBySignatario((int) $request->user()->id, $from, $to);

        return $this->csvStream('relatorio_produtividade_signatarios.csv', [
            'cliente_id',
            'name',
            'email',
            'approvals',
            'rejections',
            'avg_response_hours',
        ], array_map(fn ($r) => [
            (string) $r['cliente_id'],
            (string) ($r['name'] ?? ''),
            (string) ($r['email'] ?? ''),
            (string) $r['approvals'],
            (string) $r['rejections'],
            $r['avg_response_hours'] === null ? '' : (string) $r['avg_response_hours'],
        ], $rows));
    }

    public function period(Request $request, ReportsQueryService $reports): View
    {
        $grain = $request->string('grain')->toString() ?: 'day';
        if (! in_array($grain, ['day', 'week', 'month'], true)) {
            $grain = 'day';
        }

        $from = $request->date('from');
        $to = $request->date('to');

        $rows = $reports->processesByPeriod((int) $request->user()->id, $grain, $from, $to);

        return view('reports.period', [
            'rows' => $rows,
            'grain' => $grain,
            'from' => $from?->format('Y-m-d'),
            'to' => $to?->format('Y-m-d'),
        ]);
    }

    public function periodCsv(Request $request, ReportsQueryService $reports): StreamedResponse
    {
        $grain = $request->string('grain')->toString() ?: 'day';
        if (! in_array($grain, ['day', 'week', 'month'], true)) {
            $grain = 'day';
        }

        $from = $request->date('from');
        $to = $request->date('to');

        $rows = $reports->processesByPeriod((int) $request->user()->id, $grain, $from, $to);

        return $this->csvStream('relatorio_processos_por_periodo.csv', ['period', 'created', 'concluded'], array_map(fn ($r) => [
            $r['period'],
            (string) $r['created'],
            (string) $r['concluded'],
        ], $rows));
    }

    public function rejections(Request $request, ReportsQueryService $reports): View
    {
        $from = $request->date('from');
        $to = $request->date('to');

        $rows = $reports->rejectionsReport((int) $request->user()->id, $from, $to);

        return view('reports.rejections', [
            'rows' => $rows,
            'from' => $from?->format('Y-m-d'),
            'to' => $to?->format('Y-m-d'),
        ]);
    }

    public function rejectionsCsv(Request $request, ReportsQueryService $reports): StreamedResponse
    {
        $from = $request->date('from');
        $to = $request->date('to');

        $rows = $reports->rejectionsReport((int) $request->user()->id, $from, $to);

        return $this->csvStream('relatorio_reprovacoes.csv', [
            'processo_id',
            'title',
            'categoria',
            'signatario',
            'email',
            'rejected_at',
            'justificativa',
        ], array_map(fn ($r) => [
            (string) $r['processo_id'],
            (string) $r['title'],
            (string) $r['categoria'],
            (string) $r['signatario'],
            (string) ($r['email'] ?? ''),
            (string) ($r['rejected_at'] ?? ''),
            (string) ($r['justificativa'] ?? ''),
        ], $rows));
    }

    /**
     * @param  array<int,string>  $header
     * @param  array<int,array<int,string>>  $lines
     */
    private function csvStream(string $filename, array $header, array $lines): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $lines): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fputcsv($out, $header);
            foreach ($lines as $line) {
                fputcsv($out, $line);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
