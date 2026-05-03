@extends('layouts.app')

@section('title', 'Fluxo de assinatura')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Fluxo de assinatura (Req. 3)</h1>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('painel') }}">Painel</a>
    </div>
    <p class="text-muted small mb-4">Processos em que você é o responsável: convites por fila, links manuais e ordem paralela/sequencial.</p>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 small">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Status</th>
                    <th>Signatários</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($processos as $p)
                    <tr>
                        <td>{{ $p->id }}</td>
                        <td>{{ $p->title }}</td>
                        <td><span class="badge text-bg-secondary">{{ $p->status }}</span></td>
                        <td>{{ $p->signatarios_count }}</td>
                        <td>
                            <a class="btn btn-primary btn-sm" href="{{ route('fluxo.show', $p) }}">Gerenciar fluxo</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-muted p-4">Nenhum processo encontrado.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
