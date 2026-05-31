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
 * Mailable para confirmación de correo en el registro.
 *
 * Implementa ShouldQueue para envío asíncrono.
 * Si no hay worker activo, usa QUEUE_CONNECTION=sync en el .env.
 *
 * CORRECCIÓN: eliminada la clase duplicada ConfirmarCorreo.php.
 * Este es el único Mailable de confirmación de correo que debe existir.
 */
class ConfirmarCorreoMailable extends Mailable implements ShouldQueue
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
    public string $correo;

    public function __construct(string $nombreCompleto, string $correo)
    {
        $this->nombreCompleto = $nombreCompleto;
        $this->correo         = $correo;
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
            subject: 'Confirma tu correo - Academia Karate-Do SMT',
        );
    }

    /**
     * Get the message content definition.
     */
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