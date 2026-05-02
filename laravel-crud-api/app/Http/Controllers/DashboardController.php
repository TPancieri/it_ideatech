<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaEvento;
use App\Models\Processo;
use App\Services\Dashboard\DashboardQueryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardQueryService $queries): View
    {
        $filters = [
            'status' => $request->string('status')->toString() ?: null,
            'category' => $request->string('category')->toString() ?: null,
            'signatario_id' => $request->integer('signatario_id') ?: null,
            'from' => $request->date('from')?->format('Y-m-d'),
            'to' => $request->date('to')?->format('Y-m-d'),
        ];

        $summary = $queries->summary();

        $olderThanDays = (int) ($request->integer('overdue_days') ?: 7);
        $overdue = $queries->overduePending($olderThanDays);

        $processos = $queries->filteredProcesses($filters)->limit(200)->get();

        return view('dashboard.index', [
            'summary' => $summary,
            'filters' => $filters,
            'categories' => $queries->categories(),
            'signatarios' => $queries->signatariosOptions(),
            'overdueDays' => $olderThanDays,
            'overdue' => $overdue,
            'processos' => $processos,
        ]);
    }

    public function show(Processo $processo): View
    {
        $processo->load([
            'responsibleUser:id,name,email',
            'signatarios:id,name,email,role,sector,status',
            'statusHistories' => function ($q): void {
                $q->orderByDesc('id');
            },
            'respostas' => function ($q): void {
                $q->with('cliente:id,name,email')->orderByDesc('id');
            },
        ]);

        $auditoria = AuditoriaEvento::query()
            ->where('subject_type', Processo::class)
            ->where('subject_id', $processo->id)
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('dashboard.show', [
            'processo' => $processo,
            'auditoria' => $auditoria,
        ]);
    }
}
