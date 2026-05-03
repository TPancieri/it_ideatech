@extends('layouts.app')

@section('title', 'Processos')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Meus processos</h1>
        <a class="btn btn-primary btn-sm" href="{{ route('processos.create') }}">Novo processo</a>
    </div>
    <p class="text-muted small">Listagem filtrada pelo seu usuário como responsável. CRUD completo: criar, editar e excluir.</p>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Categoria</th>
                    <th>Status</th>
                    <th>Documento</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($processos as $p)
                    <tr>
                        <td>{{ $p->id }}</td>
                        <td>{{ $p->title }}</td>
                        <td>{{ $p->category }}</td>
                        <td><span class="badge text-bg-secondary">{{ $p->status }}</span></td>
                        <td class="small">{{ $p->document_path ? 'Sim' : '—' }}</td>
                        <td class="d-flex flex-wrap gap-1">
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('dashboard.show', $p) }}">Detalhe</a>
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('fluxo.show', $p) }}">Fluxo</a>
                            <a class="btn btn-outline-dark btn-sm" href="{{ route('processos.edit', $p) }}">Editar</a>
                            <form method="post" action="{{ route('processos.destroy', $p) }}" class="d-inline" onsubmit="return confirm('Excluir este processo?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-muted p-4">
                            Nenhum processo em que você é o <strong>responsável</strong>.
                            Processos criados antes pela API/Postman usam o <code>responsible_user_id</code> que foi enviado naquela hora (outro usuário ou outro id) e por isso não aparecem aqui.
                            O <strong>Dashboard</strong> lista processos de forma mais ampla para acompanhamento operacional.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($processos->hasPages())
            <div class="card-footer">{{ $processos->links() }}</div>
        @endif
    </div>
@endsection
