@extends('layouts.app')

@section('title', 'Painel')

@section('content')
    <h1 class="h3 mb-4">Painel</h1>
    <p class="text-muted mb-4">Acesso rápido às áreas do teste prático.</p>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Signatários (Req. 1)</h2>
                    <p class="small text-muted mb-3">Formulário web no lugar do cadastro só via Postman.</p>
                    <a class="btn btn-primary btn-sm" href="{{ route('signatarios.index') }}">Gerenciar signatários</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Processos (Req. 2)</h2>
                    <p class="small text-muted mb-3">Criar processo, anexar documento e vincular signatários pela interface.</p>
                    <a class="btn btn-primary btn-sm me-1" href="{{ route('processos.create') }}">Novo processo</a>
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('processos.index') }}">Meus processos</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Fluxo de assinatura (Req. 3)</h2>
                    <p class="small text-muted mb-3">Convites, links para aprovar/reprovar na web pública e ordem paralela ou sequencial.</p>
                    <a class="btn btn-primary btn-sm" href="{{ route('fluxo.index') }}">Abrir fluxo</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Dashboard (Req. 4)</h2>
                    <p class="small text-muted mb-3">Indicadores e lista de processos.</p>
                    <a class="btn btn-primary btn-sm" href="{{ route('dashboard.index') }}">Abrir dashboard</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Relatórios (Req. 5)</h2>
                    <p class="small text-muted mb-3">Relatórios gerenciais e export CSV.</p>
                    <a class="btn btn-primary btn-sm" href="{{ route('reports.status') }}">Abrir relatórios</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Análise (Req. 6)</h2>
                    <p class="small text-muted mb-3">Consultas agregadas.</p>
                    <a class="btn btn-primary btn-sm" href="{{ route('analytics.index') }}">Abrir análise</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Auditoria (Req. 8)</h2>
                    <p class="small text-muted mb-3">Eventos registrados no sistema.</p>
                    <a class="btn btn-primary btn-sm" href="{{ route('auditoria.index') }}">Abrir auditoria</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">API REST</h2>
                    <p class="small text-muted mb-3">Processos, convites e fluxo continuam em <code>/api/...</code> (Postman ou cliente HTTP).</p>
                    <span class="badge text-bg-secondary">Base: /api</span>
                </div>
            </div>
        </div>
    </div>
@endsection
