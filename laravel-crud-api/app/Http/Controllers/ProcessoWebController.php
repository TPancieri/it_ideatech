<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Processo;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProcessoWebController extends Controller
{
    public function index(): View
    {
        $processos = Processo::query()
            ->where('responsible_user_id', auth()->id())
            ->orderByDesc('id')
            ->paginate(20);

        return view('processos.index', compact('processos'));
    }

    public function create(): View
    {
        $clientes = Cliente::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('processos.create', compact('clientes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|max:255',
            'document' => 'nullable|file|mimes:pdf,jpeg,jpg,png,webp|max:10240',
            'signatarios' => 'nullable|array',
            'signatarios.*' => 'integer|exists:clientes,id',
        ]);

        $processo = null;

        DB::transaction(function () use ($request, $validated, &$processo): void {
            $processo = Processo::query()->create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'responsible_user_id' => $request->user()->id,
                'status' => 'pending',
            ]);

            $ids = array_values(array_unique(array_map('intval', $validated['signatarios'] ?? [])));
            foreach ($ids as $clienteId) {
                if (! Cliente::query()->whereKey($clienteId)->where('status', 'active')->exists()) {
                    continue;
                }
                $processo->signatarios()->syncWithoutDetaching([
                    $clienteId => ['sort_order' => 0],
                ]);
            }

            if ($request->hasFile('document')) {
                $disk = 'public';
                $path = $request->file('document')->store('processos/documents', $disk);
                $processo->document_path = $path;
                $processo->save();
            }
        });

        $processo->refresh();

        AuditLogger::log(
            acao: 'processo.criado',
            subject: $processo,
            actor: $request->user(),
            before: null,
            after: AuditLogger::processoSnapshot($processo),
            meta: ['via' => 'web_processos'],
            request: $request,
        );

        return redirect()->route('processos.index')->with('success', 'Processo criado com sucesso.');
    }
}
