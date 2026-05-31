<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable para recuperación de contraseña.
 *
 * Implementa ShouldQueue para que el envío sea asíncrono cuando
 * QUEUE_CONNECTION != 'sync'. Si no hay worker activo en Railway,
 * cambia QUEUE_CONNECTION=sync en el .env para envío inmediato.
 */
class cambiarcontrasenniaMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Número de intentos antes de marcar el job como fallido.
     */
    public int $tries = 3;

    /**
     * Segundos de espera entre intentos.
     */
    public int $backoff = 10;

    public string $nombreCompleto;
    public string $token;

    public function __construct(string $nombrecompleto, string $token)
    {
        $this->nombreCompleto = $nombrecompleto;
        $this->token          = $token;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address', 'academiacentralkaratedosmt@gmail.com'),
                config('mail.from.name',    'Academia Karate-Do SMT')
            ),
            subject: 'Recuperación de Contraseña - Academia Karate-Do SMT',
        );
    }

    /**
     * Get the message content definition.
     */
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

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}