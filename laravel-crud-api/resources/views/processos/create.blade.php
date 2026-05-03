@extends('layouts.app')

@section('title', 'Novo processo')

@section('content')
    <h1 class="h3 mb-4">Novo processo digital</h1>
    <p class="text-muted small mb-4">Você será registrado como <strong>responsável</strong> automaticamente. Status inicial: <code>pending</code>. Opcional: documento (PDF ou imagem) e signatários ativos.</p>

    <div class="card shadow-sm" style="max-width: 720px;">
        <div class="card-body">
            <form method="post" action="{{ route('processos.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="title">Título</label>
                    <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required maxlength="255">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="description">Descrição</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" required>{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="category">Categoria / tipo</label>
                    <input class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category') }}" required maxlength="255">
                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="document">Documento (opcional)</label>
                    <input class="form-control @error('document') is-invalid @enderror" type="file" id="document" name="document" accept=".pdf,.png,.jpg,.jpeg,.webp">
                    <div class="form-text">PDF ou imagem, até 10 MB.</div>
                    @error('document')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <div class="form-label">Signatários (opcional)</div>
                    <p class="small text-muted">Apenas signatários <strong>ativos</strong>. Ordem inicial em modo paralelo (<code>sort_order = 0</code>); ajuste fino pode ser feito pela API.</p>
                    @forelse ($clientes as $c)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="signatarios[]" value="{{ $c->id }}" id="sig_{{ $c->id }}"
                                @checked(collect(old('signatarios', []))->contains((string) $c->id))>
                            <label class="form-check-label" for="sig_{{ $c->id }}">{{ $c->name }} — {{ $c->email }}</label>
                        </div>
                    @empty
                        <div class="text-muted small">Nenhum signatário ativo. Cadastre em <a href="{{ route('signatarios.create') }}">Signatários</a>.</div>
                    @endforelse
                    @error('signatarios')<div class="text-danger small">{{ $message }}</div>@enderror
                    @error('signatarios.*')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary">Criar processo</button>
                <a class="btn btn-outline-secondary" href="{{ route('processos.index') }}">Cancelar</a>
            </form>
        </div>
    </div>
@endsection
