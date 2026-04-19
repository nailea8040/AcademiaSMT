<?php

namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactoApiController extends Controller
{
    /**
     * POST /api/contacto  (ruta pública, sin autenticación)
     */
    public function enviar(Request $request)
    {
        $validated = $request->validate([
            'nombre'   => 'required|string|max:100',
            'correo'   => 'required|email',
            'telefono' => 'required|string|max:20',
            'mensaje'  => 'required|string|max:1000',
        ]);
 
        try {
            $destinatario = 'academiacentralkaratedosmt@gmail.com';
 
            Mail::send('emails.contacto', $validated, function ($mail) use ($destinatario, $validated) {
                $mail->to($destinatario)
                     ->subject('Nuevo mensaje de contacto - Academia SMT')
                     ->replyTo($validated['correo'], $validated['nombre']);
            });
 
            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado correctamente.',
            ]);
 
        } catch (\Exception $e) {
            Log::error('ContactoApi@enviar: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el mensaje.',
            ], 500);
        }
    }
}