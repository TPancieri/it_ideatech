@auth
    <nav class="navbar navbar-expand navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('painel') }}">{{ config('app.name') }}</a>
            <div class="navbar-nav ms-auto flex-row gap-2 align-items-center">
                <a class="nav-link text-white-50" href="{{ route('painel') }}">Início</a>
                <a class="nav-link text-white-50" href="{{ route('signatarios.index') }}">Signatários</a>
                <a class="nav-link text-white-50" href="{{ route('dashboard.index') }}">Dashboard</a>
                <a class="nav-link text-white-50" href="{{ route('reports.status') }}">Relatórios</a>
                <a class="nav-link text-white-50" href="{{ route('analytics.index') }}">Análise</a>
                <a class="nav-link text-white-50" href="{{ route('auditoria.index') }}">Auditoria</a>
                <span class="nav-link text-white-50 small">{{ Auth::user()->name }}</span>
                <form action="{{ route('logout') }}" method="post" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Sair</button>
                </form>
            </div>
        </div>
    </nav>
@endauth
