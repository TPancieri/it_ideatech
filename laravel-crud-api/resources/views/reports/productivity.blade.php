<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório — Produtividade por signatário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Relatório — Produtividade por signatário</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.index') }}">Todos os relatórios</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('painel') }}">Painel</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('dashboard.index') }}">Dashboard</a>
            <form action="{{ route('logout') }}" method="post" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">Sair</button>
            </form>
            <a class="btn btn-outline-primary btn-sm"
               href="{{ route('reports.productivity.csv', ['from' => $from, 'to' => $to]) }}">Export CSV</a>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('reports.productivity') }}" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label mb-0">De</label>
                    <input class="form-control" type="date" name="from" value="{{ $from }}">
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0">Até</label>
                    <input class="form-control" type="date" name="to" value="{{ $to }}">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit">Aplicar</button>
                </div>
            </form>
            <div class="text-muted small mt-2">
                Contagens vêm de <code>processo_respostas</code>. O tempo médio usa a <strong>primeira resposta</strong> do signatário em cada processo vs <code>processos.created_at</code>.
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Signatário</th>
                        <th class="text-end">Aprovações</th>
                        <th class="text-end">Reprovações</th>
                        <th class="text-end">Tempo médio (h)</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($rows as $r)
                        <tr>
                            <td>{{ $r['cliente_id'] }}</td>
                            <td>{{ $r['name'] ?? '—' }} <span class="text-muted">({{ $r['email'] ?? '—' }})</span></td>
                            <td class="text-end">{{ $r['approvals'] }}</td>
                            <td class="text-end">{{ $r['rejections'] }}</td>
                            <td class="text-end">
                                @if ($r['avg_response_hours'] === null)
                                    <span class="text-muted">—</span>
                                @else
                                    {{ number_format($r['avg_response_hours'], 2, ',', '.') }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
