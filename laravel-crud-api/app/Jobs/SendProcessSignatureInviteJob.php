<?php

namespace App\Jobs;

use App\Mail\ProcessSignatureInviteMail;
use App\Models\Cliente;
use App\Models\Processo;
use App\Services\AuditLogger;
use App\Services\ProcessSigningTokenService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendProcessSignatureInviteJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $processoId,
        public int $clienteId,
        public int $ttlHours,
    ) {
    }

    public function handle(ProcessSigningTokenService $tokens): void
    {
        $processo = Processo::query()->find($this->processoId);
        $cliente = Cliente::query()->find($this->clienteId);

        if (! $processo || ! $cliente) {
            return;
        }

        $attached = $processo->signatarios()->where('clientes.id', $cliente->id)->exists();
        if (! $attached) {
            return;
        }

        $issued = $tokens->issue($processo, $cliente, $this->ttlHours);

        Mail::to($cliente->email)->send(new ProcessSignatureInviteMail(
            processo: $processo,
            cliente: $cliente,
            plainToken: $issued['plain_token'],
        ));

        AuditLogger::log(
            acao: 'processo.email_convite_enviado',
            subject: $processo,
            actor: $cliente,
            meta: [
                'cliente_id' => $cliente->id,
                'ttl_hours' => $this->ttlHours,
            ],
        );
    }
}
