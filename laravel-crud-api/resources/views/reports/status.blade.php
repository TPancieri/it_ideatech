<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório — Processos por status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Relatório — Processos por status</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.index') }}">Todos os relatórios</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('painel') }}">Painel</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('dashboard.index') }}">Dashboard</a>
            <form action="{{ route('logout') }}" method="post" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">Sair</button>
            </form>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('reports.status.csv') }}">Export CSV</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                    <tr>
                        <th>Status</th>
                        <th class="text-end">Quantidade</th>
                        <th class="text-end">% do total</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($rows as $r)
                        <tr>
                            <td><span class="badge text-bg-secondary">{{ $r['status'] }}</span></td>
                            <td class="text-end">{{ $r['count'] }}</td>
                            <td class="text-end">{{ number_format($r['percent'], 2, ',', '.') }}%</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="text-muted small">
                O percentual é calculado sobre o total atual de processos no banco (<code>count(status) / total</code>).
            </div>
        </div>
    </div>
</div>
</body>
</html>
