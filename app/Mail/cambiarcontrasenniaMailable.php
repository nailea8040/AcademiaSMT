<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class cambiarcontrasenniaMailable extends Mailable
{
    use SerializesModels;

    public string $nombreCompleto;
    public string $token;

    public function __construct(string $nombrecompleto, string $token)
    {
        $this->nombreCompleto = $nombrecompleto;
        $this->token          = $token;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
            subject: 'Recuperación de Contraseña - Academia Karate-Do SMT',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'ResetPasswordViews.mensajecambiarcontrasennia',
            with: [
                'nombreCompleto' => $this->nombreCompleto,
                'token'          => $this->token,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}