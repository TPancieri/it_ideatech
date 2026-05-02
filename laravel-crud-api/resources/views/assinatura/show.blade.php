<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Assinatura</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; padding: 24px;">
    @if (session('status'))
        <p><strong>{{ session('status') }}</strong></p>
    @endif

    @error('fluxo')
        <p style="color: red;">{{ $message }}</p>
    @enderror

    @if (! $valid)
        <p>Link inválido, expirado ou já utilizado.</p>
    @else
        <h2>{{ $processo->title }}</h2>
        <p><strong>Signatário:</strong> {{ $cliente->name }} ({{ $cliente->email }})</p>

        <form method="post" action="{{ url('/assinatura/'.$token.'/aprovar') }}" style="margin-bottom: 16px;">
            @csrf
            <button type="submit">Aprovar</button>
        </form>

        <form method="post" action="{{ url('/assinatura/'.$token.'/reprovar') }}">
            @csrf
            <div style="margin-bottom: 8px;">
                <label for="justificativa"><strong>Justificativa (obrigatória para reprovar)</strong></label><br>
                <textarea id="justificativa" name="justificativa" rows="5" cols="60">{{ old('justificativa') }}</textarea>
            </div>
            @error('justificativa')
                <p style="color: red;">{{ $message }}</p>
            @enderror
            <button type="submit">Reprovar</button>
        </form>
    @endif
</body>
</html>
