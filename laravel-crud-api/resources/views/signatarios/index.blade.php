@extends('layouts.app')

@section('title', 'Signatários')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Signatários</h1>
        <a class="btn btn-primary btn-sm" href="{{ route('signatarios.create') }}">Novo signatário</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Cargo</th>
                    <th>Setor</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($clientes as $c)
                    <tr>
                        <td>{{ $c->id }}</td>
                        <td>{{ $c->name }}</td>
                        <td>{{ $c->email }}</td>
                        <td>{{ $c->role }}</td>
                        <td>{{ $c->sector }}</td>
                        <td><span class="badge text-bg-secondary">{{ $c->status }}</span></td>
                        <td class="text-nowrap">
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('signatarios.edit', $c) }}">Editar</a>
                            @if ($c->status === 'active')
                                <form action="{{ route('signatarios.destroy', $c) }}" method="post" class="d-inline" onsubmit="return confirm('Inativar este signatário?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Inativar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-muted p-4">Nenhum signatário cadastrado.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($clientes->hasPages())
            <div class="card-footer">{{ $clientes->links() }}</div>
        @endif
    </div>
@endsection
