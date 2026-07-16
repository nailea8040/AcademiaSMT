<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Torneo;
use App\Models\CategoriaTorneo;
use App\Models\Inscripcion;
use App\Models\Llave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BracketController extends Controller
{
    public function index(int $torneoId, int $categoriaId)
    {
        $llaves = Llave::where('id_categoria_torneo', $categoriaId)
            ->with([
                'competidor1:id_inscripcion,nombre_completo,dojo_procedencia',
                'competidor2:id_inscripcion,nombre_completo,dojo_procedencia',
                'ganador:id_inscripcion,nombre_completo',
            ])
            ->orderBy('ronda', 'desc')
            ->orderBy('posicion')
            ->get();

        return response()->json(['ok' => true, 'data' => $llaves]);
    }

    public function generar(Request $request, int $torneoId, int $categoriaId)
    {
        $torneo = Torneo::findOrFail($torneoId);
        $categoria = CategoriaTorneo::where('id_torneo', $torneoId)->findOrFail($categoriaId);

        $inscritos = Inscripcion::where('id_torneo', $torneoId)
            ->where('id_categoria_torneo', $categoriaId)
            ->where('estado', 'activa')
            ->get();

        if ($inscritos->count() < 2) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Se necesitan al menos 2 competidores para generar llaves',
            ], 422);
        }

        Llave::where('id_categoria_torneo', $categoriaId)->delete();

        $n = $inscritos->count();
        $potencia = 1;
        while ($potencia < $n) {
            $potencia *= 2;
        }
        $byesNecesarios = $potencia - $n;

        $ordenados = $this->ordenarAntiDojo($inscritos);

        $posiciones = array_fill(0, $potencia, null);
        $slotsUsados = 0;

        for ($i = 0; $i < $byesNecesarios; $i++) {
            $posiciones[$i * 2] = 'BYE';
        }

        $idxInscrito = 0;
        for ($i = 0; $i < $potencia; $i++) {
            if ($posiciones[$i] === 'BYE') continue;
            if ($idxInscrito < $ordenados->count()) {
                $posiciones[$i] = $ordenados[$idxInscrito]->id_inscripcion;
                $idxInscrito++;
            }
        }

        $totalRondas = (int) log($potencia, 2);
        $llavesCreadas = [];
        $ordenJuego = 1;

        $llavesRondaActual = [];

        for ($i = 0; $i < $potencia; $i += 2) {
            $insc1 = $posiciones[$i] === 'BYE' ? null : $posiciones[$i];
            $insc2 = $posiciones[$i + 1] === 'BYE' ? null : $posiciones[$i + 1];

            $esBye = ($posiciones[$i] === 'BYE' || $posiciones[$i + 1] === 'BYE');

            $ganadorId = null;
            if ($posiciones[$i] === 'BYE' && $posiciones[$i + 1] !== 'BYE') {
                $ganadorId = $posiciones[$i + 1];
            } elseif ($posiciones[$i + 1] === 'BYE' && $posiciones[$i] !== 'BYE') {
                $ganadorId = $posiciones[$i];
            }

            $llave = Llave::create([
                'id_categoria_torneo' => $categoriaId,
                'ronda'               => $totalRondas,
                'posicion'            => ($i / 2) + 1,
                'id_inscripcion_1'    => $insc1,
                'id_inscripcion_2'    => $insc2,
                'ganador_id'          => $ganadorId,
                'es_bye'              => $esBye ? 1 : 0,
                'estado'              => $ganadorId ? 'completada' : 'pendiente',
                'orden_juego'         => $ordenJuego++,
            ]);

            $llavesRondaActual[] = $llave;
            $llavesCreadas[] = $llave;
        }

        for ($r = $totalRondas - 1; $r >= 1; $r--) {
            $siguienteRonda = [];
            for ($i = 0; $i < count($llavesRondaActual); $i += 2) {
                $llave = Llave::create([
                    'id_categoria_torneo' => $categoriaId,
                    'ronda'               => $r,
                    'posicion'            => ($i / 2) + 1,
                    'orden_juego'         => $ordenJuego++,
                ]);
                $siguienteRonda[] = $llave;
                $llavesCreadas[] = $llave;
            }
            $llavesRondaActual = $siguienteRonda;
        }

        $llaveFinal = $llavesRondaActual[0] ?? null;
        if ($llaveFinal) {
            $semi1 = Llave::where('id_categoria_torneo', $categoriaId)
                ->where('ronda', 2)->where('posicion', 1)->first();
            $semi2 = Llave::where('id_categoria_torneo', $categoriaId)
                ->where('ronda', 2)->where('posicion', 2)->first();

            if ($semi1) {
                Llave::create([
                    'id_categoria_torneo' => $categoriaId,
                    'ronda'               => 0,
                    'posicion'            => 1,
                    'es_tercer_lugar'     => 1,
                    'orden_juego'         => $ordenJuego++,
                ]);
            }
        }

        $categoria->update(['estado' => 'en_curso']);

        $llaves = Llave::where('id_categoria_torneo', $categoriaId)
            ->with(['competidor1:id_inscripcion,nombre_completo,dojo_procedencia', 'competidor2:id_inscripcion,nombre_completo,dojo_procedencia', 'ganador:id_inscripcion,nombre_completo'])
            ->orderBy('ronda', 'desc')
            ->orderBy('posicion')
            ->get();

        return response()->json([
            'ok'      => true,
            'mensaje' => "Se generaron {$llavesCreadas->count()} llaves para {$n} competidores (llave de {$potencia}, {$byesNecesarios} BYEs)",
            'data'    => $llaves,
        ]);
    }

    public function updateNodo(Request $request, int $torneoId, int $llaveId)
    {
        $llave = Llave::findOrFail($llaveId);

        $request->validate([
            'ganador_id' => 'required|integer|exists:inscripcion,id_inscripcion',
        ]);

        $ganadorId = $request->ganador_id;

        DB::beginTransaction();
        try {
            $llave->update([
                'ganador_id' => $ganadorId,
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
                        $siguienteLlave->update(['id_inscripcion_1' => $ganadorId]);
                    } else {
                        $siguienteLlave->update(['id_inscripcion_2' => $ganadorId]);
                    }
                }
            } elseif ($siguienteRonda === 0) {
                $llaveFinal = Llave::where('id_categoria_torneo', $llave->id_categoria_torneo)
                    ->where('ronda', 1)
                    ->first();

                if ($llaveFinal) {
                    $esImpar = $llave->posicion % 2 !== 0;
                    if ($esImpar) {
                        $llaveFinal->update(['id_inscripcion_1' => $ganadorId]);
                    } else {
                        $llaveFinal->update(['id_inscripcion_2' => $ganadorId]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'ok'      => true,
                'mensaje' => 'Ganador registrado y bracket actualizado',
                'data'    => $llave->fresh()->load(['competidor1', 'competidor2', 'ganador']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function dragDrop(Request $request, int $torneoId)
    {
        $request->validate([
            'llave_id'       => 'required|integer|exists:llave,id_llave',
            'nueva_posicion' => 'required|integer|min:1',
            'slot'           => 'required|in:1,2',
        ]);

        $llave = Llave::findOrFail($request->llave_id);
        $inscripcionId = $request->slot === 1 ? $llave->id_inscripcion_1 : $llave->id_inscripcion_2;

        if ($request->slot === 1) {
            $llave->update(['id_inscripcion_1' => $inscripcionId]);
        } else {
            $llave->update(['id_inscripcion_2' => $inscripcionId]);
        }

        return response()->json(['ok' => true, 'mensaje' => 'Competidor reubicado']);
    }

    private function ordenarAntiDojo($inscritos)
    {
        $porDojo = $inscritos->groupBy('dojo_procedencia');
        $dojosOrdenados = $porDojo->sortByDesc(fn($grupo) => $grupo->count());

        $resultado = collect();
        $maxEnDojo = $dojosOrdenados->first()->count() ?? 0;
        $totalInscritos = $inscritos->count();

        if ($maxEnDojo > $totalInscritos / 2) {
            return $inscritos->shuffle();
        }

        $roundRobin = [];
        foreach ($dojosOrdenados as $dojo => $grupo) {
            $roundRobin[] = $grupo->values();
        }

        $maxLen = max(array_map('count', $roundRobin));

        for ($i = 0; $i < $maxLen; $i++) {
            foreach ($roundRobin as $grupo) {
                if (isset($grupo[$i])) {
                    $resultado->push($grupo[$i]);
                }
            }
        }

        return $resultado;
    }
}
