<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaEvento;
use App\Models\Cliente;
use App\Models\Processo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditoriaEvento::query()->orderByDesc('id');

        if ($request->filled('acao')) {
            $term = $request->string('acao')->toString();
            $query->where('acao', 'like', '%'.$term.'%');
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->string('subject_type')->toString());
        }

        if ($request->filled('processo_id')) {
            $pid = $request->integer('processo_id');
            $query->where('subject_type', Processo::class)->where('subject_id', $pid);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        $events = $query->paginate(40)->withQueryString();

        return view('auditoria.index', [
            'events' => $events,
            'filters' => [
                'acao' => $request->string('acao')->toString(),
                'subject_type' => $request->string('subject_type')->toString(),
                'processo_id' => $request->filled('processo_id') ? $request->integer('processo_id') : null,
                'from' => $request->date('from')?->format('Y-m-d'),
                'to' => $request->date('to')?->format('Y-m-d'),
            ],
            'subjectTypes' => [
                Processo::class => 'Processo',
                Cliente::class => 'Cliente',
            ],
        ]);
    }
}
