<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Análise de Dados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Análise de dados (Req. 6)</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('painel') }}">Painel</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('dashboard.index') }}">Dashboard</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('auditoria.index') }}">Auditoria</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.index') }}">Relatórios</a>
            <form action="{{ route('logout') }}" method="post" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">Sair</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('analytics.index') }}" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label mb-0">Período (criação)</label>
                    <div class="input-group">
                        <input class="form-control" type="date" name="from" value="{{ $snapshot['options']['from'] }}">
                        <input class="form-control" type="date" name="to" value="{{ $snapshot['options']['to'] }}">
                    </div>
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0">Grão</label>
                    <select class="form-select" name="grain">
                        <option value="day" @selected($snapshot['options']['grain'] === 'day')>Dia</option>
                        <option value="week" @selected($snapshot['options']['grain'] === 'week')>Semana</option>
                        <option value="month" @selected($snapshot['options']['grain'] === 'month')>Mês</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit">Atualizar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Tempo médio de aprovação</div>
                    <div class="fs-4 fw-semibold">
                        @if ($snapshot['avg_approval_hours'] === null)
                            <span class="text-muted">sem amostra</span>
                        @else
                            {{ $snapshot['avg_approval_hours'] }} horas
                        @endif
                    </div>
                    <div class="text-muted small">
                        Base: primeira transição para <code>approved</code> em <code>processo_status_histories</code>.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Categoria com maior volume</div>
                    @php($topCat = $snapshot['category_volume'][0] ?? null)
                    <div class="fs-5 fw-semibold">
                        {{ $topCat['category'] ?? '—' }}
                    </div>
                    <div class="text-muted small">
                        {{ $topCat ? ($topCat['count'].' processos') : 'sem dados' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Status com mais processos (agora)</div>
                    @php($topSt = $snapshot['status_volume'][0] ?? null)
                    <div class="fs-5 fw-semibold">
                        {{ $topSt['status'] ?? '—' }}
                    </div>
                    <div class="text-muted small">
                        {{ $topSt ? ($topSt['count'].' processos') : 'sem dados' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Signatários que mais aprovam/reprovam</div>
                <div class="card-body">
                    @if (count($snapshot['top_signatarios']) === 0)
                        <div class="text-muted">Sem respostas registradas.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                <tr>
                                    <th>Signatário</th>
                                    <th class="text-end">Aprovações</th>
                                    <th class="text-end">Reprovações</th>
                                    <th class="text-end">Tempo médio (h)</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($snapshot['top_signatarios'] as $r)
                                    <tr>
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
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Processos criados por período</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                            <tr>
                                <th>Período</th>
                                <th class="text-end">Criados</th>
                                <th class="text-end">Concluídos</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($snapshot['created_by_period'] as $r)
                                <tr>
                                    <td class="text-nowrap">{{ $r['period'] }}</td>
                                    <td class="text-end">{{ $r['created'] }}</td>
                                    <td class="text-end">{{ $r['concluded'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted small">
                        “Concluídos” considera primeira transição para <code>approved</code> ou <code>rejected</code> no histórico.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

