<?php

namespace App\Mail;

use App\Models\Cliente;
use App\Models\Processo;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Convites são disparados de forma síncrona a partir de {@see SendProcessSignatureInviteJob}
 * (via {@see \Illuminate\Support\Facades\Bus::dispatchSync}); este mailable não deve ir para
 * outra fila, senão o operador precisaria de worker só para o e-mail.
 */
class ProcessSignatureInviteMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Processo $processo,
        public Cliente $cliente,
        public string $plainToken,
        public ?string $documentPublicUrl = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Assinatura/Aprovação pendente: '.$this->processo->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.process_signature_invite',
        );
    }
}
