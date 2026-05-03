@extends('layouts.app')

@section('title', 'Fluxo — '.$processo->title)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h3 mb-0">Fluxo de assinatura</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('fluxo.index') }}">Lista</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('processos.index') }}">Processos</a>
        </div>
    </div>

    <p class="mb-1"><strong>{{ $processo->title }}</strong> <span class="badge text-bg-secondary">{{ $processo->status }}</span></p>
    <p class="text-muted small mb-4">Modo atual: {{ $modoFluxo }}. Signatários usam a página pública de assinatura (sem login). Convites por e-mail ficam com o mesmo link recuperável abaixo (token guardado só como hash + texto cifrado com a chave da aplicação).</p>
    <div class="alert alert-light border small mb-4 py-2">
        <strong>Convites e e-mail:</strong> ao usar <em>Enviar convites</em> (ou ao criar o processo na web com signatários), o sistema processa <strong>na hora</strong> (<code>Bus::dispatchSync</code>): tokens e envio de e-mail ficam prontos antes de recarregar esta página. Um worker (<code>queue:work</code>) só é necessário para <strong>outros</strong> jobs que você vier a enfileirar no projeto.
    </div>

    @if (session('assinatura_url_unica'))
        <div class="alert alert-warning small mb-3">
            <p class="mb-2">
                <strong>URL de assinatura</strong>
                @if (session('assinatura_link_para'))
                    — <span class="text-body">{{ session('assinatura_link_para') }}</span>
                @endif
            </p>
            <p class="text-muted small mb-0">
                @if (session('assinatura_link_revelado'))
                    Link recuperado do convite (cifrado no banco). Copie agora; quem tiver a URL pode assinar até expirar ou ser usada.
                @elseif (session('assinatura_link_nova_emissao'))
                    Novo token emitido. Copie agora; o valor em claro não fica salvo sem cifra.
                @else
                    Copie a URL abaixo.
                @endif
            </p>
            <div class="input-group input-group-sm mt-2">
                <input type="text" class="form-control font-monospace small" readonly value="{{ session('assinatura_url_unica') }}" id="link-unico">
                <button type="button" class="btn btn-outline-dark" onclick="navigator.clipboard.writeText(document.getElementById('link-unico').value)">Copiar</button>
            </div>
        </div>
    @elseif (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 small">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">Enviar convites (fila)</div>
                <div class="card-body">
                    <p class="small text-muted">Equivale ao <code>POST /api/processo/{id}/convites</code>: um envio por signatário, <strong>na hora</strong> (token + e-mail antes de recarregar).</p>
                    <form method="post" action="{{ route('fluxo.convites', $processo) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-auto">
                            <label class="form-label small mb-0" for="ttl_hours">TTL (horas)</label>
                            <input class="form-control form-control-sm" type="number" name="ttl_hours" id="ttl_hours" value="72" min="1" max="720">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm">Enfileirar convites</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">Gerar link manual</div>
                <div class="card-body">
                    <p class="small text-muted">Útil em testes ou quando o signatário não recebe e-mail. O token em texto claro não fica salvo após esta tela.</p>
                    <form method="post" action="{{ route('fluxo.link', $processo) }}" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <label class="form-label small mb-0" for="cliente_id">Signatário</label>
                            <select class="form-select form-select-sm" name="cliente_id" id="cliente_id" required>
                                @foreach ($processo->signatarios as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} — {{ $c->email }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <label class="form-label small mb-0" for="ttl_link">TTL (horas)</label>
                            <input class="form-control form-control-sm" type="number" name="ttl_hours" id="ttl_link" value="72" min="1" max="720">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-outline-primary btn-sm" @if($processo->signatarios->isEmpty()) disabled @endif>Gerar URL</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header fw-semibold">Ordem de aprovação (paralelo vs sequencial)</div>
        <div class="card-body">
            <p class="small text-muted mb-3">Todos com <code>sort_order = 0</code> → paralelo. Valores <code>1, 2, 3…</code> distintos → sequencial na ordem crescente.</p>
            @if ($processo->signatarios->isEmpty())
                <p class="text-muted small mb-0">Nenhum signatário vinculado. Edite o processo em <a href="{{ route('processos.create') }}">novo processo</a> ou use a API.</p>
            @else
                <form method="post" action="{{ route('fluxo.ordem', $processo) }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>Signatário</th>
                                <th style="width:8rem">sort_order</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($processo->signatarios as $c)
                                <tr>
                                    <td>{{ $c->name }} <span class="text-muted small">{{ $c->email }}</span></td>
                                    <td>
                                        <input class="form-control form-control-sm" type="number" min="0" name="sort_order[{{ $c->id }}]" value="{{ (int) $c->pivot->sort_order }}" required>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Salvar ordem</button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Tokens emitidos (metadados)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>Signatário</th>
                                <th>Expira</th>
                                <th>Consumido</th>
                                <th>Link</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($processo->assinaturaTokens->sortByDesc('id') as $t)
                                <tr>
                                    <td class="small">{{ $t->cliente?->name ?? '—' }}</td>
                                    <td class="small">{{ $t->expires_at?->format('d/m/Y H:i') }}</td>
                                    <td class="small">{{ $t->consumed_at ? $t->consumed_at->format('d/m/Y H:i') : '—' }}</td>
                                    <td class="small">
                                        @if ($t->consumed_at)
                                            <span class="text-muted">Usado</span>
                                        @elseif ($t->expires_at?->isPast())
                                            <span class="text-muted">Expirado</span>
                                        @elseif (filled($t->invite_plain_ciphertext))
                                            <form method="post" action="{{ route('fluxo.revelar', [$processo, $t]) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-primary btn-sm py-0 px-2">Exibir link</button>
                                            </form>
                                        @else
                                            <span class="text-muted" title="Emitido antes da coluna de recuperação">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted small p-3">Nenhum token ainda.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Respostas registradas</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>Signatário</th>
                                <th>Tipo</th>
                                <th>Quando</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($processo->respostas->sortByDesc('id') as $r)
                                <tr>
                                    <td class="small">{{ $r->cliente?->name ?? '—' }}</td>
                                    <td class="small"><span class="badge text-bg-light">{{ $r->tipo }}</span></td>
                                    <td class="small">{{ $r->created_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted small p-3">Nenhuma resposta ainda.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
