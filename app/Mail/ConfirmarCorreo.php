<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class ConfirmarCorreoMailable extends Mailable
{
    use SerializesModels;

    public $nombre;
    public $correo;

    public function __construct($nombre, $correo)
    {
        $this->nombre = $nombre;
        $this->correo = $correo;
    }

    public function build()
    {
        return $this->view('emails.confirmar-correo')
                    ->subject('Confirma tu correo');
    }
}

