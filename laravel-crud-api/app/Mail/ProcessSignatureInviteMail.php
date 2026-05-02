<?php

namespace App\Mail;

use App\Models\Cliente;
use App\Models\Processo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProcessSignatureInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Processo $processo,
        public Cliente $cliente,
        public string $plainToken,
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
