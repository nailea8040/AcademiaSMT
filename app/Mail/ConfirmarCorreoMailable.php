<?php


namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmarCorreoMailable extends Mailable
{
    use Queueable, SerializesModels;
    
    public $nombreCompleto;
    public $correo;

    /**
     * Create a new message instance.
     */
    public function __construct($nombreCompleto, $correo)
    {
        $this->nombreCompleto = $nombreCompleto;
        $this->correo = $correo;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
{
    return new Envelope(
        from: new Address(
            env('MAIL_FROM_ADDRESS', 'academiacentralkaratedosmt@gmail.com'),
            env('MAIL_FROM_NAME', 'Academia Karate-Do SMT')
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
                'correo' => $this->correo,
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