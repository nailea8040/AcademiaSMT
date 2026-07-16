<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Torneo;
use App\Models\FaseResponsable;
use App\Models\AutorizacionFase;
use App\Models\LogFase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TorneoController extends Controller
{
    public function index()
    {
        $torneos = Torneo::withCount('categorias')
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json(['ok' => true, 'data' => $torneos]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'          => 'required|string|max:200',
            'descripcion'     => 'nullable|string',
            'fecha'           => 'required|date',
            'hora_inicio'     => 'nullable|date_format:H:i',
            'ubicacion'       => 'nullable|string|max:300',
            'id_plantilla'    => 'nullable|integer|exists:plantilla_categoria,id_plantilla',
            'tatami_asignado' => 'nullable|integer|min:1',
        ]);

        $torneo = Torneo::create($validated);

        return response()->json(['ok' => true, 'data' => $torneo], 201);
    }

    public function show(int $id)
    {
        $torneo = Torneo::with(['categorias.inscripciones', 'puntajesDojo'])
            ->findOrFail($id);

        return response()->json(['ok' => true, 'data' => $torneo]);
    }

    public function update(Request $request, int $id)
    {
        $torneo = Torneo::findOrFail($id);

        $validated = $request->validate([
            'nombre'          => 'sometimes|string|max:200',
            'descripcion'     => 'nullable|string',
            'fecha'           => 'sometimes|date',
            'hora_inicio'     => 'nullable|date_format:H:i',
            'ubicacion'       => 'nullable|string|max:300',
            'id_plantilla'    => 'nullable|integer|exists:plantilla_categoria,id_plantilla',
            'tatami_asignado' => 'nullable|integer|min:1',
        ]);

        $torneo->update($validated);

        return response()->json(['ok' => true, 'data' => $torneo]);
    }

    public function destroy(int $id)
    {
        $torneo = Torneo::findOrFail($id);
        $torneo->delete();

        return response()->json(['ok' => true, 'mensaje' => 'Torneo eliminado']);
    }

    /**
     * Cambiar fase del torneo con validación de NIP.
     */
    public function cambiarFase(Request $request, int $id)
    {
        $torneo = Torneo::findOrFail($id);

        $request->validate([
            'fase' => 'required|enum:graficacion,mesas,premiacion,memoria',
            'nip'  => 'required|string|min:4|max:8',
        ]);

        $fase = $request->fase;
        $nip  = $request->nip;

        $fasesPermitidas = [
            'graficacion' => ['desde' => ['inscripcion'], 'hacia' => 'graficacion'],
            'mesas'       => ['desde' => ['graficacion'], 'hacia' => 'mesas'],
            'premiacion'  => ['desde' => ['mesas'], 'hacia' => 'premiacion'],
            'memoria'     => ['desde' => ['premiacion'], 'hacia' => 'memoria'],
        ];

        if (!isset($fasesPermitidas[$fase])) {
            return response()->json(['ok' => false, 'mensaje' => 'Fase no válida para transición'], 422);
        }

        if (!in_array($torneo->estado, $fasesPermitidas[$fase]['desde'])) {
            return response()->json([
                'ok'      => false,
                'mensaje' => "No se puede transicionar de '{$torneo->estado}' a '{$fase}'",
            ], 422);
        }

        $responsable = FaseResponsable::where('fase', $fase)
            ->where('activo', 1)
            ->first();

        if (!$responsable) {
            return response()->json(['ok' => false, 'mensaje' => 'No hay responsable configurado para esta fase'], 404);
        }

        if (!$responsable->validarNip($nip)) {
            return response()->json(['ok' => false, 'mensaje' => 'NIP incorrecto para esta fase'], 401);
        }

        $usuario = $request->user();
        if ($responsable->id_usuario !== $usuario->id_usuario && !$usuario->esAdmin()) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'No eres el responsable autorizado para esta fase',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $faseAnterior = $torneo->estado;

            $torneo->update(['estado' => $fasesPermitidas[$fase]['hacia']]);

            AutorizacionFase::create([
                'id_torneo'           => $torneo->id_torneo,
                'fase'                => $fase,
                'id_usuario_autoriza' => $usuario->id_usuario,
                'nip_hash'            => $responsable->nip_hash,
                'ip_address'          => $request->ip(),
            ]);

            LogFase::create([
                'id_torneo'     => $torneo->id_torneo,
                'fase_anterior' => $faseAnterior,
                'fase_nueva'    => $fasesPermitidas[$fase]['hacia'],
                'id_usuario'    => $usuario->id_usuario,
            ]);

            DB::commit();

            return response()->json([
                'ok'      => true,
                'mensaje' => "Torneo avanzado a fase: {$fasesPermitidas[$fase]['hacia']}",
                'data'    => $torneo->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'mensaje' => 'Error al cambiar fase: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Gestionar responsables de fase (solo admin).
     */
    public function storeResponsable(Request $request)
    {
        $request->validate([
            'fase'       => 'required|enum:graficacion,mesas,premiacion,memoria|unique:fase_responsable,fase',
            'id_usuario' => 'required|integer|exists:usuario,id_usuario',
            'nip'        => 'required|string|min:4|max:8',
        ]);

        $responsable = FaseResponsable::create([
            'fase'     => $request->fase,
            'id_usuario' => $request->id_usuario,
            'nip_hash' => Hash::make($request->nip),
        ]);

        return response()->json(['ok' => true, 'data' => $responsable->makeHidden('nip_hash')], 201);
    }

    public function responsables()
    {
        $responsables = FaseResponsable::with('usuario:id_usuario,nombre,apaterno,amaterno')
            ->get()
            ->map(function ($r) {
                return [
                    'id_responsable' => $r->id_responsable,
                    'fase'           => $r->fase,
                    'usuario'        => $r->usuario->nombre_completo ?? 'N/A',
                    'activo'         => $r->activo,
                ];
            });

        return response()->json(['ok' => true, 'data' => $responsables]);
    }
}
