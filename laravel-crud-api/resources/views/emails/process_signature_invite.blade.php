<p>Olá {{ $cliente->name }},</p>

<p>Você foi solicitado(a) a analisar o processo: <strong>{{ $processo->title }}</strong>.</p>

<p>
    Acesse o link abaixo para aprovar ou reprovar:
    <br>
    <a href="{{ route('assinatura.show', ['token' => $plainToken]) }}">{{ route('assinatura.show', ['token' => $plainToken]) }}</a>
</p>

<p>Este link é pessoal e intransferível.</p>
