<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — Processos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Dashboard operacional</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('painel') }}">Painel</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('processos.index') }}">Processos</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('signatarios.index') }}">Signatários</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('auditoria.index') }}">Auditoria</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.index') }}">Relatórios</a>
            <form action="{{ route('logout') }}" method="post" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">Sair</button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Total</div>
                    <div class="fs4 fw-semibold">{{ $summary['total_processos'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Pendentes</div>
                    <div class="fs4 fw-semibold">{{ $summary['pending'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Em aprovação</div>
                    <div class="fs4 fw-semibold">{{ $summary['in_approval'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Aprovados</div>
                    <div class="fs4 fw-semibold">{{ $summary['approved'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Reprovados</div>
                    <div class="fs4 fw-semibold">{{ $summary['rejected'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Cancelados</div>
                    <div class="fs4 fw-semibold">{{ $summary['canceled'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Processos por status</div>
                <div class="card-body" style="max-height: 320px;">
                    <canvas id="chartStatus"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Volume por status (barras)</div>
                <div class="card-body" style="max-height: 320px;">
                    <canvas id="chartStatusBar"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <div class="text-muted small">Tempo médio de aprovação</div>
                <div class="fs-5 fw-semibold">
                    @if ($summary['avg_approval_hours'] === null)
                        <span class="text-muted">sem amostra</span>
                    @else
                        {{ $summary['avg_approval_hours'] }} horas
                    @endif
                </div>
                <div class="text-muted small mt-1">
                    Calculado como média de <code>h.created_at - processos.created_at</code> para eventos com <code>to_status = approved</code>.
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">Processos pendentes há mais de N dias</div>
        <div class="card-body">
            <form method="get" action="{{ route('dashboard.index') }}" class="row g-2 align-items-end mb-3">
                @foreach ($filters as $k => $v)
                    @if ($v !== null && $v !== '')
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endif
                @endforeach
                <div class="col-auto">
                    <label class="form-label mb-0">N dias</label>
                    <input class="form-control" type="number" min="1" name="overdue_days" value="{{ $overdueDays }}">
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-primary" type="submit">Atualizar</button>
                </div>
            </form>

            @if (count($overdue) === 0)
                <div class="text-muted">Nenhum processo pendente acima do limite.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Categoria</th>
                            <th>Criado em</th>
                            <th>Dias pendente</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($overdue as $row)
                            <tr>
                                <td>{{ $row['id'] }}</td>
                                <td>{{ $row['title'] }}</td>
                                <td>{{ $row['category'] }}</td>
                                <td>{{ optional($row['created_at'])->format('Y-m-d H:i') }}</td>
                                <td>{{ $row['days_pending'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">Filtros</div>
        <div class="card-body">
            <form method="get" action="{{ route('dashboard.index') }}" class="row g-3">
                <input type="hidden" name="overdue_days" value="{{ $overdueDays }}">

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">(todos)</option>
                        @foreach (['pending','in_approval','approved','rejected','canceled'] as $st)
                            <option value="{{ $st }}" @selected(($filters['status'] ?? null) === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Categoria</label>
                    <select class="form-select" name="category">
                        <option value="">(todas)</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" @selected(($filters['category'] ?? null) === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Signatário</label>
                    <select class="form-select" name="signatario_id">
                        <option value="">(qualquer)</option>
                        @foreach ($signatarios as $s)
                            <option value="{{ $s['id'] }}" @selected((int) ($filters['signatario_id'] ?? 0) === (int) $s['id'])>
                                {{ $s['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Período (criação)</label>
                    <div class="input-group">
                        <input type="date" class="form-control" name="from" value="{{ $filters['from'] ?? '' }}">
                        <input type="date" class="form-control" name="to" value="{{ $filters['to'] ?? '' }}">
                    </div>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Aplicar filtros</button>
                    <a class="btn btn-outline-secondary" href="{{ route('dashboard.index') }}">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">Processos</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Status</th>
                        <th>Categoria</th>
                        <th>Responsável</th>
                        <th>Criado em</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($processos as $p)
                        <tr>
                            <td>{{ $p->id }}</td>
                            <td>{{ $p->title }}</td>
                            <td><span class="badge text-bg-secondary">{{ $p->status }}</span></td>
                            <td>{{ $p->category }}</td>
                            <td>{{ $p->responsibleUser?->email }}</td>
                            <td>{{ $p->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('dashboard.show', $p) }}">Detalhes</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="text-muted small mt-2">
                Mostrando até 200 registros (sem paginação). Indicadores e gráficos consideram apenas processos em que você é o <strong>responsável</strong>.
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = ['Pendentes', 'Em aprovação', 'Aprovados', 'Reprovados', 'Cancelados'];
    const data = [
        {{ (int) $summary['pending'] }},
        {{ (int) $summary['in_approval'] }},
        {{ (int) $summary['approved'] }},
        {{ (int) $summary['rejected'] }},
        {{ (int) $summary['canceled'] }},
    ];
    const colors = ['#6c757d', '#0dcaf0', '#198754', '#dc3545', '#fd7e14'];
    if (typeof Chart === 'undefined') return;
    new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 1 }] },
        options: { plugins: { legend: { position: 'bottom' } }, maintainAspectRatio: false }
    });
    new Chart(document.getElementById('chartStatusBar'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Quantidade', data, backgroundColor: colors }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }, maintainAspectRatio: false }
    });
});
</script>
</body>
</html>
