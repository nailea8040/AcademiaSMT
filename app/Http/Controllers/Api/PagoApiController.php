<?php
// app/Http/Controllers/Api/PagoApiController.php
// Reemplaza tu archivo existente con este.

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagoApiController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    //  GET /api/pagos
    //  Admin/sensei: todos | Alumno/tutor: solo los suyos
    // ──────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        try {
            $user  = $request->user();
            $query = DB::table('pago as p')
                ->join('usuario as u', 'p.id_usuario', '=', 'u.id_usuario')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->select(
                    'p.id_pago', 'p.monto', 'p.motivo_pago', 'p.fecha_pago',
                    'p.referencia_pago', 'p.estado_pago', 'p.id_usuario',
                    'p.id_tipo_pago', 'p.mp_preference_id', 'p.mp_payment_id', 'p.mp_status',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"),
                    'tp.nombre_tipo'
                )
                ->orderBy('p.fecha_pago', 'desc');

            if (in_array($user->rol, ['alumno', 'tutor'])) {
                $query->where('p.id_usuario', $user->id_usuario);
            }

            return response()->json(['success' => true, 'data' => $query->get()]);

        } catch (\Exception $e) {
            Log::error('PagoApi@index: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener los pagos.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    //  POST /api/pagos
    //  Registra el pago en BD y devuelve URL de MercadoPago para pagar.
    //  Solo admin y sensei pueden registrar pagos a otros alumnos.
    //  Un alumno puede iniciar su propio pago.
    // ──────────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $user = $request->user();

        // Alumnos solo pueden pagar a su propio id
        if (
            in_array($user->rol, ['alumno', 'tutor']) &&
            (int) $request->input('id_alumno') !== (int) $user->id_usuario
        ) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para registrar pagos de otros alumnos.'], 403);
        }

        $validated = $request->validate([
            'id_alumno'      => 'required|exists:usuario,id_usuario',
            'id_tipo_pago'   => 'required|exists:tipo_pago,id_tipo_pago',
            'monto'          => 'required|numeric|min:0',
            'fechaPago'      => 'required|date',
            'estadoPago'     => 'required|string|max:20',
            'motivoPago'     => 'nullable|string|max:100',
            'referenciaPago' => 'nullable|string|max:100',
            // Si viene true, se genera la preferencia de MercadoPago
            'pagar_en_linea' => 'boolean',
        ]);

        try {
            // 1. Insertar en BD con estado inicial
            $id = DB::table('pago')->insertGetId([
                'id_usuario'      => $validated['id_alumno'],
                'id_tipo_pago'    => $validated['id_tipo_pago'],
                'monto'           => $validated['monto'],
                'motivo_pago'     => $validated['motivoPago']    ?? null,
                'fecha_pago'      => $validated['fechaPago'],
                'referencia_pago' => $validated['referenciaPago'] ?? null,
                // Si va a pagar en línea lo dejamos Pendiente hasta confirmar webhook
                'estado_pago'     => ($validated['pagar_en_linea'] ?? false) ? 'Pendiente' : $validated['estadoPago'],
            ]);

            $respuesta = ['success' => true, 'message' => 'Pago registrado.', 'id' => $id];

            // 2. Si solicita pago en línea, crear preferencia en MercadoPago
            if ($validated['pagar_en_linea'] ?? false) {
                // Obtener email del alumno para enviarlo a MP
                $alumno = DB::table('usuario')
                    ->where('id_usuario', $validated['id_alumno'])
                    ->select('nombre', 'apaterno', 'correo')
                    ->first();

                $mpService    = new MercadoPagoService();
                $preferencia  = $mpService->crearPreferencia([
                    'id_pago'       => $id,
                    'monto'         => $validated['monto'],
                    'motivo'        => $validated['motivoPago'] ?? 'Pago Academia',
                    'alumno_email'  => $alumno->correo ?? 'alumno@academia.com',
                    'alumno_nombre' => "{$alumno->nombre} {$alumno->apaterno}",
                ]);

                // Guardar preference_id en BD
                DB::table('pago')->where('id_pago', $id)->update([
                    'mp_preference_id' => $preferencia['id'],
                ]);

                $respuesta['mercadopago'] = [
                    'preference_id'       => $preferencia['id'],
                    // La app móvil abre esta URL en el navegador o webview
                    'init_point'          => $preferencia['init_point'],
                    // En sandbox usa esta URL para pruebas
                    'sandbox_init_point'  => $preferencia['sandbox_init_point'],
                ];
            }

            return response()->json($respuesta, 201);

        } catch (\Exception $e) {
            Log::error('PagoApi@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al registrar el pago.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    //  POST /api/pagos/webhook   (sin auth:sanctum — MP no envía token)
    //  MercadoPago llama aquí al aprobar / rechazar un pago.
    //  IMPORTANTE: agrega esta ruta FUERA del grupo auth:sanctum en api.php
    // ──────────────────────────────────────────────────────────────────────
    public function webhook(Request $request)
    {
        // MP envía type=payment y data.id con el payment_id
        $type      = $request->input('type');
        $paymentId = $request->input('data.id');

        if ($type !== 'payment' || ! $paymentId) {
            // Responder 200 siempre para que MP no reintente
            return response()->json(['ok' => true]);
        }

        try {
            // Consultar el estado real del pago a la API de MP
            $accessToken = config('services.mercadopago.access_token');
            $response    = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

            if (! $response->ok()) {
                Log::warning("MP Webhook: no se pudo consultar payment {$paymentId}");
                return response()->json(['ok' => false], 200); // 200 igual para evitar reintentos
            }

            $pago       = $response->json();
            $mpStatus   = $pago['status']             ?? null;  // approved, pending, rejected
            $externalRef = $pago['external_reference'] ?? null; // id_pago interno

            if (! $externalRef) {
                return response()->json(['ok' => true]);
            }

            // Mapear estado de MP al estado interno
            $estadoInterno = match ($mpStatus) {
                'approved'    => 'Completado',
                'pending', 'in_process', 'authorized' => 'Pendiente',
                default       => 'Fallido',
            };

            DB::table('pago')
                ->where('id_pago', (int) $externalRef)
                ->update([
                    'mp_payment_id' => (string) $paymentId,
                    'mp_status'     => $mpStatus,
                    'estado_pago'   => $estadoInterno,
                    // Guardar el id del pago de MP como referencia
                    'referencia_pago' => "MP-{$paymentId}",
                ]);

            Log::info("MP Webhook: pago #{$externalRef} → {$estadoInterno} (MP: {$mpStatus})");

        } catch (\Exception $e) {
            Log::error('MP Webhook error: ' . $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  GET /api/pagos/historial/{idUsuario}
    // ──────────────────────────────────────────────────────────────────────
    public function historialAlumno(Request $request, int $id_usuario)
    {
        $user = $request->user();

        if (in_array($user->rol, ['alumno', 'tutor']) && (int) $user->id_usuario !== (int) $id_usuario) {
            return response()->json(['success' => false, 'message' => 'Sin permiso.'], 403);
        }

        try {
            $pagos = DB::table('pago as p')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->where('p.id_usuario', $id_usuario)
                ->select(
                    'p.id_pago', 'p.monto', 'p.motivo_pago', 'p.fecha_pago',
                    'p.referencia_pago', 'p.estado_pago', 'p.mp_status',
                    'tp.nombre_tipo'
                )
                ->orderBy('p.fecha_pago', 'desc')
                ->get();

            return response()->json(['success' => true, 'data' => $pagos]);

        } catch (\Exception $e) {
            Log::error('PagoApi@historialAlumno: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener historial.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    //  GET /api/tipos-pago
    // ──────────────────────────────────────────────────────────────────────
    public function tiposPago()
    {
        try {
            return response()->json([
                'success' => true,
                'data'    => DB::table('tipo_pago')->orderBy('id_tipo_pago')->get(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }
}