<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="mx-auto" style="max-width: 880px;">
        <h1 class="h3 mb-4">Trilha de assinatura digital</h1>
        <p class="text-muted mb-4">Crie sua conta para usar o sistema como <strong>usuário responsável</strong> pelos processos, ou entre com e-mail e senha.</p>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header fw-semibold">Cadastro</div>
                    <div class="card-body">
                        <form method="post" action="{{ route('register') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" for="reg_name">Nome</label>
                                <input class="form-control @error('name') is-invalid @enderror" id="reg_name" name="name" value="{{ old('name') }}" required autocomplete="name">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="reg_email">E-mail</label>
                                <input class="form-control @error('email') is-invalid @enderror" type="email" id="reg_email" name="email" value="{{ old('email') }}" required autocomplete="email">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="reg_password">Senha</label>
                                <input class="form-control @error('password') is-invalid @enderror" type="password" id="reg_password" name="password" required autocomplete="new-password">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="reg_password_confirmation">Confirmar senha</label>
                                <input class="form-control" type="password" id="reg_password_confirmation" name="password_confirmation" required autocomplete="new-password">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Criar conta e entrar</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header fw-semibold">Login</div>
                    <div class="card-body">
                        <form method="post" action="{{ route('login.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" for="login_email">E-mail</label>
                                <input class="form-control @error('email') is-invalid @enderror" type="email" id="login_email" name="email" value="{{ old('email') }}" required autocomplete="username">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="login_password">Senha</label>
                                <input class="form-control @error('password') is-invalid @enderror" type="password" id="login_password" name="password" required autocomplete="current-password">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                                <label class="form-check-label" for="remember">Manter conectado</label>
                            </div>
                            <button type="submit" class="btn btn-outline-primary w-100">Entrar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <p class="small text-muted mt-4 mb-0">Links públicos de assinatura (convite) continuam acessíveis pela URL do e-mail, sem login.</p>
    </div>
</div>
</body>
</html>
