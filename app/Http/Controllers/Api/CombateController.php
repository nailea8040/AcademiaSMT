<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Llave;
use App\Models\Combate;
use App\Models\CategoriaTorneo;
use Illuminate\Http\Request;

class CombateController extends Controller
{
    public function store(Request $request, int $llaveId)
    {
        $llave = Llave::findOrFail($llaveId);

        $request->validate([
            'id_inscripcion_rojo' => 'required|integer|exists:inscripcion,id_inscripcion',
            'id_inscripcion_azul' => 'required|integer|exists:inscripcion,id_inscripcion',
        ]);

        $combate = Combate::create([
            'id_llave'            => $llaveId,
            'id_inscripcion_rojo' => $request->id_inscripcion_rojo,
            'id_inscripcion_azul' => $request->id_inscripcion_azul,
        ]);

        $llave->update(['estado' => 'en_curso']);

        return response()->json(['ok' => true, 'data' => $combate->load(['competidorRojo', 'competidorAzul'])], 201);
    }

    public function update(Request $request, int $combateId)
    {
        $combate = Combate::findOrFail($combateId);

        $request->validate([
            'puntos_rojo'   => 'sometimes|integer|min:0',
            'puntos_azul'   => 'sometimes|integer|min:0',
            'ganador'       => 'required|in:rojo,azul',
            'ippon_rojo'    => 'sometimes|boolean',
            'ippon_azul'    => 'sometimes|boolean',
            'wazari_rojo'   => 'sometimes|boolean',
            'wazari_azul'   => 'sometimes|boolean',
            'yuko_rojo'     => 'sometimes|integer|min:0',
            'yuko_azul'     => 'sometimes|integer|min:0',
            'tiempo_segundos' => 'nullable|integer',
            'observaciones' => 'nullable|string',
        ]);

        $campos = $request->only([
            'puntos_rojo', 'puntos_azul', 'ganador',
            'ippon_rojo', 'ippon_azul', 'wazari_rojo', 'wazari_azul',
            'yuko_rojo', 'yuko_azul', 'tiempo_segundos', 'observaciones',
        ]);

        $combate->update($campos);

        $ganadorInscripcionId = $combate->ganador === 'rojo'
            ? $combate->id_inscripcion_rojo
            : $combate->id_inscripcion_azul;

        $llave = $combate->llave;
        $llave->update([
            'ganador_id' => $ganadorInscripcionId,
            'estado'     => 'completada',
        ]);

        $siguienteRonda = $llave->ronda - 1;
        $siguientePos = (int) ceil($llave->posicion / 2);

        if ($siguienteRonda >= 1) {
            $siguienteLlave = Llave::where('id_categoria_torneo', $llave->id_categoria_torneo)
                ->where('ronda', $siguienteRonda)
                ->where('posicion', $siguientePos)
                ->first();

            if ($siguienteLlave) {
                $esImpar = $llave->posicion % 2 !== 0;
                if ($esImpar) {
                    $siguienteLlave->update(['id_inscripcion_1' => $ganadorInscripcionId]);
                } else {
                    $siguienteLlave->update(['id_inscripcion_2' => $ganadorInscripcionId]);
                }
            }
        }

        return response()->json([
            'ok'      => true,
            'mensaje' => 'Combate finalizado. Ganador: ' . ($combate->ganador === 'rojo' ? 'Aka (Rojo)' : 'Ao (Azul)'),
            'data'    => $combate->fresh()->load(['competidorRojo', 'competidorAzul']),
        ]);
    }

    public function show(int $combateId)
    {
        $combate = Combate::with(['llave', 'competidorRojo', 'competidorAzul'])->findOrFail($combateId);
        return response()->json(['ok' => true, 'data' => $combate]);
    }

    public function porCategoria(int $torneoId, int $categoriaId)
    {
        $llaveIds = Llave::where('id_categoria_torneo', $categoriaId)->pluck('id_llave');

        $combates = Combate::whereIn('id_llave', $llaveIds)
            ->with(['competidorRojo', 'competidorAzul', 'llave'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['ok' => true, 'data' => $combates]);
    }
}
