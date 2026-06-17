<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactoController extends Controller
{
    /**
     * Envía un correo de contacto desde el formulario de landing
     */
    public function enviar(Request $request)
    {
        // Validar los datos del formulario
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'correo' => 'required|email',
            'telefono' => 'required|string|max:20',
            'mensaje' => 'required|string|max:1000',
        ]);

        try {
            // Correo destinatario desde config/app.php → CONTACTO_EMAIL
            $destinatario = config('app.contacto_email');
            
            // Enviar correo al administrador con los datos del contacto
            Mail::send('emails.contacto', [
                'nombre' => $validated['nombre'],
                'correo' => $validated['correo'],
                'telefono' => $validated['telefono'],
                'mensaje' => $validated['mensaje'],
            ], function ($mail) use ($destinatario, $validated) {
                $mail->to($destinatario)
                     ->subject('Nuevo mensaje de contacto - Academia SMT')
                     ->replyTo($validated['correo'], $validated['nombre']);
            });

            return redirect('/landing')->with('success', '¡Mensaje enviado correctamente! Nos pondremos en contacto pronto.');

        } catch (\Exception $e) {
            Log::error('Error al enviar correo de contacto: ' . $e->getMessage());
            return redirect('/landing')->with('error', 'Error al enviar el mensaje. Por favor intenta nuevamente.');
        }
    }
}