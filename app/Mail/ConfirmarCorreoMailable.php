<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmarCorreoMailable extends Mailable
{
    use SerializesModels;

    public string $nombreCompleto;
    public string $correo;

    public function __construct(string $nombreCompleto, string $correo)
    {
        $this->nombreCompleto = $nombreCompleto;
        $this->correo         = $correo;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
            subject: 'Confirma tu correo - Academia Karate-Do SMT',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'RegisterViews.mensajeconfirmarcorreo',
            with: [
                'nombreCompleto' => $this->nombreCompleto,
                'correo'         => $this->correo,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}