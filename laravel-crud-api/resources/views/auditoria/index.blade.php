<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auditoria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Auditoria (Req. 8)</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('painel') }}">Painel</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('dashboard.index') }}">Dashboard</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.status') }}">Relatórios</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('analytics.index') }}">Análise</a>
            <form action="{{ route('logout') }}" method="post" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">Sair</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('auditoria.index') }}" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label mb-0">Ação (contém)</label>
                    <input class="form-control" type="text" name="acao" value="{{ $filters['acao'] }}" placeholder="ex.: processo.">
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0">Tipo do subject</label>
                    <select class="form-select" name="subject_type">
                        <option value="">—</option>
                        @foreach ($subjectTypes as $class => $label)
                            <option value="{{ $class }}" @selected($filters['subject_type'] === $class)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0">Processo ID</label>
                    <input class="form-control" type="number" name="processo_id" min="1" value="{{ $filters['processo_id'] }}">
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0">De</label>
                    <input class="form-control" type="date" name="from" value="{{ $filters['from'] }}">
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0">Até</label>
                    <input class="form-control" type="date" name="to" value="{{ $filters['to'] }}">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit">Filtrar</button>
                    <a class="btn btn-outline-secondary" href="{{ route('auditoria.index') }}">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Quando</th>
                        <th>Ação</th>
                        <th>Subject</th>
                        <th>Actor</th>
                        <th>IP</th>
                        <th>Detalhes</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($events as $e)
                        <tr>
                            <td class="text-nowrap">{{ $e->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="text-nowrap"><code>{{ $e->acao }}</code></td>
                            <td class="small">
                                @if ($e->subject_type)
                                    {{ class_basename($e->subject_type) }} #{{ $e->subject_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small">
                                @if ($e->actor_type)
                                    {{ class_basename($e->actor_type) }} #{{ $e->actor_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-muted small">{{ $e->ip ?? '—' }}</td>
                            <td>
                                <details class="small">
                                    <summary class="text-primary" style="cursor:pointer;">before / after / meta</summary>
                                    <pre class="mt-2 mb-0 small bg-white border rounded p-2">{{ json_encode(['before' => $e->before, 'after' => $e->after, 'meta' => $e->meta], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted p-4">Nenhum evento encontrado.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($events->hasPages())
            <div class="card-footer">{{ $events->links() }}</div>
        @endif
    </div>
</div>
</body>
</html>
