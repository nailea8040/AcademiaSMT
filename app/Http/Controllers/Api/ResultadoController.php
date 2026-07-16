<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Torneo;
use App\Models\CategoriaTorneo;
use App\Models\Llave;
use App\Models\Resultado;
use App\Models\PuntajeDojo;
use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultadoController extends Controller
{
    public function resultados(Request $request, int $torneoId)
    {
        $torneo = Torneo::with(['categorias' => function ($q) {
            $q->with(['resultados.inscripcion']);
        }])->findOrFail($torneoId);

        $resultados = [];
        foreach ($torneo->categorias as $cat) {
            $resultados[$cat->id_categoria_torneo] = [
                'nombre'   => $cat->nombre_categoria,
                'estado'   => $cat->estado,
                'podium'   => $cat->resultados->sortBy('puesto')->values(),
            ];
        }

        return response()->json(['ok' => true, 'data' => $resultados]);
    }

    public function finalizarCategoria(Request $request, int $torneoId, int $categoriaId)
    {
        $categoria = CategoriaTorneo::where('id_torneo', $torneoId)->findOrFail($categoriaId);

        DB::beginTransaction();
        try {
            $final = Llave::where('id_categoria_torneo', $categoriaId)
                ->where('ronda', 1)
                ->first();

            if (!$final || !$final->ganador_id) {
                return response()->json(['ok' => false, 'mensaje' => 'La final no tiene ganador aún'], 422);
            }

            Resultado::create([
                'id_categoria_torneo' => $categoriaId,
                'id_inscripcion'      => $final->ganador_id,
                'puesto'              => '1ro',
                'puntos_torneo'       => 100,
            ]);

            $perdedorFinal = $final->id_inscripcion_1 == $final->ganador_id
                ? $final->id_inscripcion_2
                : $final->id_inscripcion_1;

            if ($perdedorFinal) {
                Resultado::create([
                    'id_categoria_torneo' => $categoriaId,
                    'id_inscripcion'      => $perdedorFinal,
                    'puesto'              => '2do',
                    'puntos_torneo'       => 75,
                ]);
            }

            $semifinales = Llave::where('id_categoria_torneo', $categoriaId)
                ->where('ronda', 2)
                ->get();

            foreach ($semifinales as $semi) {
                if ($semi->ganador_id) {
                    $perdedorSemi = $semi->id_inscripcion_1 == $semi->ganador_id
                        ? $semi->id_inscripcion_2
                        : $semi->id_inscripcion_1;

                    if ($perdedorSemi) {
                        $existe = Resultado::where('id_categoria_torneo', $categoriaId)
                            ->where('id_inscripcion', $perdedorSemi)
                            ->exists();

                        if (!$existe) {
                            Resultado::create([
                                'id_categoria_torneo' => $categoriaId,
                                'id_inscripcion'      => $perdedorSemi,
                                'puesto'              => '3ro',
                                'puntos_torneo'       => 50,
                            ]);
                        }
                    }
                }
            }

            $categoria->update(['estado' => 'finalizada']);

            $this->actualizarPuntajeDojo($torneoId);

            DB::commit();

            $podium = Resultado::where('id_categoria_torneo', $categoriaId)
                ->with('inscripcion:id_inscripcion,nombre_completo,dojo_procedencia')
                ->orderBy('puesto')
                ->get();

            return response()->json([
                'ok'      => true,
                'mensaje' => 'Categoría finalizada. Pódium generado.',
                'data'    => $podium,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function puntajeDojo(int $torneoId)
    {
        $puntajes = PuntajeDojo::where('id_torneo', $torneoId)
            ->orderByDesc('total_puntos')
            ->get();

        return response()->json(['ok' => true, 'data' => $puntajes]);
    }

    public function mejorCompetidor(int $torneoId)
    {
        $resultados = DB::table('resultado')
            ->join('inscripcion', 'resultado.id_inscripcion', '=', 'inscripcion.id_inscripcion')
            ->join('categoria_torneo', 'resultado.id_categoria_torneo', '=', 'categoria_torneo.id_categoria_torneo')
            ->where('categoria_torneo.id_torneo', $torneoId)
            ->select(
                'inscripcion.id_inscripcion',
                'inscripcion.nombre_completo',
                'inscripcion.genero',
                'inscripcion.dojo_procedencia',
                DB::raw('SUM(CASE WHEN resultado.puesto = "1ro" THEN 1 ELSE 0 END) as oros'),
                DB::raw('SUM(CASE WHEN resultado.puesto = "2do" THEN 1 ELSE 0 END) as platas'),
                DB::raw('SUM(CASE WHEN resultado.puesto = "3ro" THEN 1 ELSE 0 END) as bronces'),
                DB::raw('COUNT(*) as total_podios')
            )
            ->groupBy('inscripcion.id_inscripcion', 'inscripcion.nombre_completo', 'inscripcion.genero', 'inscripcion.dojo_procedencia')
            ->orderByDesc('total_podios')
            ->orderByDesc('oros')
            ->get();

        $masculino = $resultados->where('genero', 'masculino')->first();
        $femenino = $resultados->where('genero', 'femenino')->first();

        $empate = false;
        $empatados = [];

        if ($masculino && $masculino->total_podios > 0) {
            $empatesM = $resultados->where('genero', 'masculino')
                ->where('total_podios', $masculino->total_podios)
                ->where('oros', $masculino->oros);

            if ($empatesM->count() > 1) {
                $empate = true;
                $empatados = array_merge($empatados, $empatesM->toArray());
            }
        }

        if ($femenino && $femenino->total_podios > 0) {
            $empatesF = $resultados->where('genero', 'femenino')
                ->where('total_podios', $femenino->total_podios)
                ->where('oros', $femenino->oros);

            if ($empatesF->count() > 1) {
                $empate = true;
                $empatados = array_merge($empatados, $empatesF->toArray());
            }
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'mejor_masculino' => $masculino,
                'mejor_femenino'  => $femenino,
                'hay_empate'      => $empate,
                'empatados'       => $empatados,
            ],
        ]);
    }

    public function resolverEmpate(Request $request, int $torneoId)
    {
        $request->validate([
            'ganador_id' => 'required|integer|exists:inscripcion,id_inscripcion',
            'fase'       => 'required|in:masculino,femenino',
        ]);

        return response()->json([
            'ok'      => true,
            'mensaje' => "Desempate resuelto para {$request->fase}",
        ]);
    }

    private function actualizarPuntajeDojo(int $torneoId)
    {
        $resultados = DB::table('resultado')
            ->join('inscripcion', 'resultado.id_inscripcion', '=', 'inscripcion.id_inscripcion')
            ->where('inscripcion.id_torneo', $torneoId)
            ->select(
                'inscripcion.dojo_procedencia',
                DB::raw('SUM(CASE WHEN resultado.puesto = "1ro" THEN 1 ELSE 0 END) as total_1ro'),
                DB::raw('SUM(CASE WHEN resultado.puesto = "2do" THEN 1 ELSE 0 END) as total_2do'),
                DB::raw('SUM(CASE WHEN resultado.puesto = "3ro" THEN 1 ELSE 0 END) as total_3ro')
            )
            ->whereNotNull('inscripcion.dojo_procedencia')
            ->groupBy('inscripcion.dojo_procedencia')
            ->get();

        PuntajeDojo::where('id_torneo', $torneoId)->delete();

        foreach ($resultados as $r) {
            PuntajeDojo::create([
                'id_torneo'    => $torneoId,
                'dojo_nombre'  => $r->dojo_procedencia,
                'puntos_1ro'   => $r->total_1ro,
                'puntos_2do'   => $r->total_2do,
                'puntos_3ro'   => $r->total_3ro,
            ]);
        }
    }
}
