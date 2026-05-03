@extends('layouts.app')

@section('title', 'Editar processo')

@section('content')
    <h1 class="h3 mb-4">Editar processo</h1>
    <p class="text-muted small mb-4">Apenas processos em que você é o <strong>responsável</strong>. Status só pode mudar conforme as transições permitidas.</p>

    <div class="card shadow-sm" style="max-width: 720px;">
        <div class="card-body">
            <form method="post" action="{{ route('processos.update', $processo) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label" for="title">Título</label>
                    <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $processo->title) }}" required maxlength="255">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="description">Descrição</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" required>{{ old('description', $processo->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="category">Categoria / tipo</label>
                    <input class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category', $processo->category) }}" required maxlength="255">
                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                        @foreach ($statusOptions as $st)
                            <option value="{{ $st }}" @selected(old('status', $processo->status) === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="document">Substituir documento (opcional)</label>
                    <input class="form-control @error('document') is-invalid @enderror" type="file" id="document" name="document" accept=".pdf,.png,.jpg,.jpeg,.webp">
                    <div class="form-text">Atual: {{ $processo->document_path ? 'Sim' : 'Nenhum' }}. PDF ou imagem, até 10 MB.</div>
                    @error('document')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <div class="form-label">Signatários</div>
                    <p class="small text-muted">Lista substituída ao salvar. Ordem fina em <a href="{{ route('fluxo.show', $processo) }}">Fluxo de assinatura</a>.</p>
                    @php
                        $selected = collect(old('signatarios', $processo->signatarios->pluck('id')->map(fn ($id) => (string) $id)->all()));
                    @endphp
                    @forelse ($clientes as $c)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="signatarios[]" value="{{ $c->id }}" id="sig_{{ $c->id }}"
                                   @checked($selected->contains((string) $c->id))>
                            <label class="form-check-label" for="sig_{{ $c->id }}">{{ $c->name }} — {{ $c->email }}</label>
                        </div>
                    @empty
                        <div class="text-muted small">Nenhum signatário ativo.</div>
                    @endforelse
                    @error('signatarios')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary">Salvar alterações</button>
                <a class="btn btn-outline-secondary" href="{{ route('processos.index') }}">Cancelar</a>
            </form>
        </div>
    </div>
@endsection
