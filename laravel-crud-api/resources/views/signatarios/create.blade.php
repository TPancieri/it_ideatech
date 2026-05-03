@extends('layouts.app')

@section('title', 'Novo signatário')

@section('content')
    <h1 class="h3 mb-4">Novo signatário</h1>

    <div class="card shadow-sm" style="max-width: 520px;">
        <div class="card-body">
            <form method="post" action="{{ route('signatarios.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="name">Nome</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">E-mail</label>
                    <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email') }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="role">Cargo</label>
                    <input class="form-control @error('role') is-invalid @enderror" id="role" name="role" value="{{ old('role') }}" required>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="sector">Setor ou departamento</label>
                    <input class="form-control @error('sector') is-invalid @enderror" id="sector" name="sector" value="{{ old('sector') }}" required>
                    @error('sector')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                        <option value="active" @selected(old('status', 'active') === 'active')>Ativo</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>Inativo</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a class="btn btn-outline-secondary" href="{{ route('signatarios.index') }}">Cancelar</a>
            </form>
        </div>
    </div>
@endsection
