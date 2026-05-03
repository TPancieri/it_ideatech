<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SignatarioWebController extends Controller
{
    public function index(): View
    {
        $clientes = Cliente::query()->orderByDesc('id')->paginate(20);

        return view('signatarios.index', compact('clientes'));
    }

    public function create(): View
    {
        return view('signatarios.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:clientes,email',
            'role' => 'required|string',
            'sector' => 'required|string',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->route('signatarios.create')
                ->withErrors($validator)
                ->withInput();
        }

        $payload = $validator->validated();
        $payload['status'] = $payload['status'] ?? 'active';

        $cliente = Cliente::query()->create($payload);

        AuditLogger::log(
            acao: 'cliente.criado',
            subject: $cliente,
            actor: $request->user(),
            before: null,
            after: AuditLogger::clienteSnapshot($cliente),
            meta: ['via' => 'web_signatarios'],
            request: $request,
        );

        return redirect()->route('signatarios.index')->with('success', 'Signatário cadastrado.');
    }

    public function edit(Cliente $cliente): View
    {
        return view('signatarios.edit', ['cliente' => $cliente]);
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:clientes,email,'.$cliente->id,
            'role' => 'required|string',
            'sector' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->route('signatarios.edit', $cliente)
                ->withErrors($validator)
                ->withInput();
        }

        $before = AuditLogger::clienteSnapshot($cliente);

        $cliente->update($validator->validated());
        $cliente->refresh();

        AuditLogger::log(
            acao: 'cliente.atualizado',
            subject: $cliente,
            actor: $request->user(),
            before: $before,
            after: AuditLogger::clienteSnapshot($cliente),
            meta: ['via' => 'web_signatarios'],
            request: $request,
        );

        return redirect()->route('signatarios.index')->with('success', 'Signatário atualizado.');
    }

    public function destroy(Request $request, Cliente $cliente): RedirectResponse
    {
        $before = AuditLogger::clienteSnapshot($cliente);

        $cliente->status = 'inactive';
        $cliente->save();
        $cliente->refresh();

        AuditLogger::log(
            acao: 'cliente.inativado',
            subject: $cliente,
            actor: $request->user(),
            before: $before,
            after: AuditLogger::clienteSnapshot($cliente),
            meta: ['via' => 'web_signatarios'],
            request: $request,
        );

        return redirect()->route('signatarios.index')->with('success', 'Signatário inativado.');
    }
}
