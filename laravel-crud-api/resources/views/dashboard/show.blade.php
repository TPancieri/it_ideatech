<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Processo #{{ $processo->id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Processo #{{ $processo->id }}</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('painel') }}">Painel</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('auditoria.index', ['processo_id' => $processo->id]) }}">Auditoria (filtro)</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('dashboard.index') }}">Voltar</a>
            <form action="{{ route('logout') }}" method="post" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">Sair</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="fw-semibold fs-5">{{ $processo->title }}</div>
                    <div class="text-muted">{{ $processo->description }}</div>
                    @if ($processo->document_path)
                        <div class="mt-2">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('processos.documento', $processo) }}" target="_blank" rel="noopener">Ver documento anexado</a>
                        </div>
                    @endif
                </div>
                <div class="col-md-4">
                    <div><span class="text-muted">Status:</span> <span class="badge text-bg-secondary">{{ $processo->status }}</span></div>
                    <div><span class="text-muted">Categoria:</span> {{ $processo->category }}</div>
                    <div><span class="text-muted">Criado em:</span> {{ $processo->created_at?->format('Y-m-d H:i') }}</div>
                    <div><span class="text-muted">Responsável:</span> {{ $processo->responsibleUser?->name }} ({{ $processo->responsibleUser?->email }})</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Histórico de status</div>
                <div class="card-body">
                    @if ($processo->statusHistories->isEmpty())
                        <div class="text-muted">Sem registros.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                <tr>
                                    <th>Quando</th>
                                    <th>De</th>
                                    <th>Para</th>
                                    <th>Motivo</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($processo->statusHistories as $h)
                                    <tr>
                                        <td class="text-nowrap">{{ $h->created_at?->format('Y-m-d H:i') }}</td>
                                        <td>{{ $h->from_status ?? '—' }}</td>
                                        <td>{{ $h->to_status }}</td>
                                        <td>{{ $h->reason ?? '—' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Respostas (signatários)</div>
                <div class="card-body">
                    @if ($processo->respostas->isEmpty())
                        <div class="text-muted">Sem respostas registradas.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                <tr>
                                    <th>Quando</th>
                                    <th>Signatário</th>
                                    <th>Tipo</th>
                                    <th>Justificativa</th>
                                    <th>IP</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($processo->respostas as $r)
                                    <tr>
                                        <td class="text-nowrap">{{ $r->created_at?->format('Y-m-d H:i') }}</td>
                                        <td>{{ $r->cliente?->name }} ({{ $r->cliente?->email }})</td>
                                        <td>{{ $r->tipo }}</td>
                                        <td>{{ $r->justificativa ?? '—' }}</td>
                                        <td class="text-muted">{{ $r->ip ?? '—' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Auditoria (eventos com este processo como subject)</span>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('auditoria.index', ['processo_id' => $processo->id]) }}">Listagem completa</a>
        </div>
        <div class="card-body">
            @if ($auditoria->isEmpty())
                <div class="text-muted">Sem auditoria registrada.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                        <tr>
                            <th>Quando</th>
                            <th>Ação</th>
                            <th>IP</th>
                            <th>Meta</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($auditoria as $a)
                            <tr>
                                <td class="text-nowrap">{{ $a->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-nowrap">{{ $a->acao }}</td>
                                <td class="text-muted">{{ $a->ip ?? '—' }}</td>
                                <td><code>{{ json_encode($a->meta, JSON_UNESCAPED_UNICODE) }}</code></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>
