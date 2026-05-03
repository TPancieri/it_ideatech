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
        $userId = (int) $request->user()->id;

        $filters = [
            'status' => $request->string('status')->toString() ?: null,
            'category' => $request->string('category')->toString() ?: null,
            'signatario_id' => $request->integer('signatario_id') ?: null,
            'from' => $request->date('from')?->format('Y-m-d'),
            'to' => $request->date('to')?->format('Y-m-d'),
        ];

        $summary = $queries->summary($userId);

        $olderThanDays = (int) ($request->integer('overdue_days') ?: 7);
        $overdue = $queries->overduePending($userId, $olderThanDays);

        $processos = $queries->filteredProcesses($userId, $filters)->limit(200)->get();

        return view('dashboard.index', [
            'summary' => $summary,
            'filters' => $filters,
            'categories' => $queries->categories($userId),
            'signatarios' => $queries->signatariosOptions($userId),
            'overdueDays' => $olderThanDays,
            'overdue' => $overdue,
            'processos' => $processos,
        ]);
    }

    public function show(Request $request, Processo $processo): View
    {
        $this->authorize('view', $processo);

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
