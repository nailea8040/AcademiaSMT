<?php
// app/Http/Controllers/PagoController.php
// Reemplaza el archivo existente con este.

namespace App\Http\Controllers;

use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagoController extends Controller
{
    // ──────────────────────────────────────────────────────────────────
    //  GET /pagos   — vista principal (sin cambios)
    // ──────────────────────────────────────────────────────────────────
    public function index()
    {
        try {
            $pagos = DB::table('pago as p')
                ->join('usuario as u', 'p.id_usuario', '=', 'u.id_usuario')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->select(
                    'p.id_pago', 'p.monto', 'p.motivo_pago', 'p.fecha_pago',
                    'p.referencia_pago', 'p.estado_pago', 'p.mp_preference_id',
                    'p.mp_payment_id', 'p.mp_status',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"),
                    'tp.nombre_tipo', 'p.id_usuario', 'p.id_tipo_pago'
                )
                ->orderBy('p.fecha_pago', 'desc')
                ->get();

            $alumnos = DB::table('usuario')
                ->where('rol', 'alumno')->where('estado', 1)
                ->select('id_usuario', DB::raw("CONCAT(nombre,' ',apaterno) AS nombre_completo"))
                ->orderBy('nombre')->get();

            $tipos_pago = DB::table('tipo_pago')->orderBy('id_tipo_pago')->get();

            dd($alumnos, $tipos_pago);
            
            return view('pagosViews.pagos', compact('pagos', 'alumnos', 'tipos_pago'));

        } catch (\Exception $e) {
            Log::error('PagoController@index: ' . $e->getMessage());
            return view('pagosViews.pagos', [
                'pagos' => collect(), 'alumnos' => collect(), 'tipos_pago' => collect(),
            ])->with('mensaje', 'Error al cargar datos.');
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  POST /pagos   — registra y, si se eligió pago en línea, redirige a MP
    // ──────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_alumno'      => 'required|exists:usuario,id_usuario',
            'id_tipo_pago'   => 'required|exists:tipo_pago,id_tipo_pago',
            'monto'          => 'required|numeric|min:0',
            'fechaPago'      => 'required|date',
            'estadoPago'     => 'required|string|max:20',
            'motivoPago'     => 'nullable|string|max:100',
            'referenciaPago' => 'nullable|string|max:100',
            // Checkbox "Pagar en línea con MercadoPago"
            'pagar_en_linea' => 'nullable|boolean',
        ]);

        $pagarEnLinea = (bool) ($validated['pagar_en_linea'] ?? false);

        try {
            $id = DB::table('pago')->insertGetId([
                'id_usuario'      => $validated['id_alumno'],
                'id_tipo_pago'    => $validated['id_tipo_pago'],
                'monto'           => $validated['monto'],
                'motivo_pago'     => $validated['motivoPago']    ?? null,
                'fecha_pago'      => $validated['fechaPago'],
                'referencia_pago' => $validated['referenciaPago'] ?? null,
                // Pendiente hasta que MP confirme
                'estado_pago'     => $pagarEnLinea ? 'Pendiente' : $validated['estadoPago'],
            ]);

            if ($pagarEnLinea) {
                $alumno = DB::table('usuario')
                    ->where('id_usuario', $validated['id_alumno'])
                    ->select('nombre', 'apaterno', 'correo')
                    ->first();

                $mpService   = new MercadoPagoService();
                $preferencia = $mpService->crearPreferencia([
                    'id_pago'       => $id,
                    'monto'         => $validated['monto'],
                    'motivo'        => $validated['motivoPago'] ?? 'Pago Academia',
                    'alumno_email'  => $alumno->correo ?? 'alumno@academia.com',
                    'alumno_nombre' => "{$alumno->nombre} {$alumno->apaterno}",
                ]);

                DB::table('pago')->where('id_pago', $id)->update([
                    'mp_preference_id' => $preferencia['id'],
                ]);

                // Redirigir al alumno al Checkout de MercadoPago
                $url = app()->environment('production')
                    ? $preferencia['init_point']
                    : $preferencia['sandbox_init_point'];

                return redirect($url);
            }

            return redirect()->route('pagos.index')
                ->with('sessionInsertado', 'true')
                ->with('mensaje', '¡Pago registrado con éxito!');

        } catch (\Exception $e) {
            Log::error('PagoController@store: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Error al registrar el pago.');
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  GET /pagos/resultado?estado=success|failure|pending&id_pago=X
    //  MercadoPago redirige aquí después del pago (back_urls)
    // ──────────────────────────────────────────────────────────────────
    public function resultado(Request $request)
    {
        $estado  = $request->query('estado');   // success | failure | pending
        $idPago  = $request->query('id_pago');

        $pago = $idPago
            ? DB::table('pago')->where('id_pago', $idPago)->first()
            : null;

        return view('pagosViews.resultado', compact('estado', 'pago'));
    }

    // ──────────────────────────────────────────────────────────────────
    //  GET /pagos/{id}/historial
    // ──────────────────────────────────────────────────────────────────
    public function historialAlumno(int $id_usuario)
    {
        try {
            $pagos = DB::table('pago as p')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->where('p.id_usuario', $id_usuario)
                ->select(
                    'p.id_pago', 'p.monto', 'p.motivo_pago', 'p.fecha_pago',
                    'p.referencia_pago', 'p.estado_pago', 'p.mp_status', 'tp.nombre_tipo'
                )
                ->orderBy('p.fecha_pago', 'desc')
                ->get();

            return response()->json($pagos);

        } catch (\Exception $e) {
            Log::error('PagoController@historialAlumno: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener historial.'], 500);
        }
    }

// ══════════════════════════════════════════════════════════════════════════════
//  AGREGA este método a tu PagoController.php existente
// ══════════════════════════════════════════════════════════════════════════════

// ──────────────────────────────────────────────────────────────────────────────
//  GET /pagos/{id_pago}/pagar
//  Muestra la página con el Payment Brick embebido.
//  Cualquier alumno autenticado puede pagar su propio pago pendiente.
// ──────────────────────────────────────────────────────────────────────────────
public function pagar(int $idPago)
{
    $pago = DB::table('pago as p')
        ->join('usuario as u', 'p.id_usuario', '=', 'u.id_usuario')
        ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
        ->where('p.id_pago', $idPago)
        ->select(
            'p.*',
            DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"),
            'u.correo',
            'tp.nombre_tipo'
        )
        ->first();

    if (! $pago) {
        abort(404, 'Pago no encontrado.');
    }

    // Solo el propio alumno o admin/sensei pueden acceder
    $user = Auth::user();
    if (
        in_array($user->rol, ['alumno', 'tutor']) &&
        (int) $user->id_usuario !== (int) $pago->id_usuario
    ) {
        abort(403, 'No tienes permiso para pagar este registro.');
    }

    // Si ya está completado no tiene sentido pagar de nuevo
    if ($pago->estado_pago === 'Completado') {
        return redirect()->route('pagos.index')
            ->with('mensaje', 'Este pago ya fue completado.')
            ->with('sessionInsertado', 'true');
    }

    // Obtener o crear preference_id para el Brick
    $preferenceId = $pago->mp_preference_id;

    if (! $preferenceId) {
        try {
            $mpService   = new \App\Services\MercadoPagoService();
            $preferencia = $mpService->crearPreferencia([
                'id_pago'       => $pago->id_pago,
                'monto'         => $pago->monto,
                'motivo'        => $pago->motivo_pago ?? 'Pago Academia',
                'alumno_email'  => $pago->correo ?? 'alumno@academia.com',
                'alumno_nombre' => $pago->nombre_alumno,
            ]);

            $preferenceId = $preferencia['id'];

            DB::table('pago')->where('id_pago', $idPago)->update([
                'mp_preference_id' => $preferenceId,
                'estado_pago'      => 'Pendiente', // asegurar que esté pendiente
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PagoController@pagar: ' . $e->getMessage());
            return redirect()->route('pagos.index')
                ->with('mensaje', 'Error al inicializar el pago. Intenta de nuevo.')
                ->with('sessionInsertado', 'false');
        }
    }

    return view('pagosViews.pagar', compact('pago', 'preferenceId'));
}
}