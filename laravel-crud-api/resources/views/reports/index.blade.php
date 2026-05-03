@extends('layouts.app')

@section('title', 'Relatórios')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Relatórios</h1>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('painel') }}">Painel</a>
    </div>
    <p class="text-muted small mb-4">Escolha o relatório. Cada um tem exportação CSV quando aplicável.</p>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Processos por status</h2>
                    <p class="small text-muted mb-3">Distribuição e percentuais por estado do processo.</p>
                    <a class="btn btn-primary btn-sm" href="{{ route('reports.status') }}">Abrir</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Produtividade de signatários</h2>
                    <p class="small text-muted mb-3">Aprovações, reprovações e tempo médio de resposta (filtro por período).</p>
                    <a class="btn btn-primary btn-sm" href="{{ route('reports.productivity') }}">Abrir</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Processos por período</h2>
                    <p class="small text-muted mb-3">Criados vs concluídos por dia, semana ou mês.</p>
                    <a class="btn btn-primary btn-sm" href="{{ route('reports.period') }}">Abrir</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Reprovações</h2>
                    <p class="small text-muted mb-3">Lista de reprovações com justificativa e signatário.</p>
                    <a class="btn btn-primary btn-sm" href="{{ route('reports.rejections') }}">Abrir</a>
                </div>
            </div>
        </div>
    </div>
@endsection
