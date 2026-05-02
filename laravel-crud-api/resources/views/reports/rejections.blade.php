<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório — Reprovações</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Relatório — Reprovações</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('dashboard.index') }}">Dashboard</a>
            <a class="btn btn-outline-primary btn-sm"
            href="{{ route('reports.rejections.csv', ['from' => $from, 'to' => $to]) }}">Export CSV</a>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('reports.rejections') }}" class="row g-2 align-items-end">
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
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                    <tr>
                        <th>Processo</th>
                        <th>Categoria</th>
                        <th>Signatário</th>
                        <th>Quando</th>
                        <th>Justificativa</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($rows as $r)
                        <tr>
                            <td class="text-nowrap">#{{ $r['processo_id'] }} — {{ $r['title'] }}</td>
                            <td>{{ $r['categoria'] }}</td>
                            <td>{{ $r['signatario'] }} <span class="text-muted">({{ $r['email'] ?? '—' }})</span></td>
                            <td class="text-nowrap">{{ $r['rejected_at'] }}</td>
                            <td>{{ $r['justificativa'] }}</td>
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
