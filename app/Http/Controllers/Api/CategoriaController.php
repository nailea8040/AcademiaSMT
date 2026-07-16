<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlantillaCategoria;
use App\Models\CategoriaDefinicion;
use App\Models\CategoriaTorneo;
use App\Models\Torneo;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    // ── Plantillas ──────────────────────────────────────────────

    public function plantillas()
    {
        $plantillas = PlantillaCategoria::with('definiciones')->get();
        return response()->json(['ok' => true, 'data' => $plantillas]);
    }

    public function storePlantilla(Request $request)
    {
        $request->validate([
            'nombre'        => 'required|string|max:100',
            'descripcion'   => 'nullable|string',
            'definiciones'  => 'required|array|min:1',
            'definiciones.*.nombre_categoria' => 'required|string|max:150',
            'definiciones.*.tipo_disciplina'  => 'required|in:kata,kumite,ambas',
            'definiciones.*.sexo'             => 'required|in:masculino,femenino,mixto',
            'definiciones.*.edad_min'         => 'nullable|integer|min:0',
            'definiciones.*.edad_max'         => 'nullable|integer|min:0',
            'definiciones.*.peso_min'         => 'nullable|numeric|min:0',
            'definiciones.*.peso_max'         => 'nullable|numeric|min:0',
            'definiciones.*.grado_min'        => 'nullable|integer|min:0',
            'definiciones.*.grado_max'        => 'nullable|integer|min:0',
        ]);

        $plantilla = PlantillaCategoria::create([
            'nombre'      => $request->nombre,
            'descripcion' => $request->descripcion,
            'id_creador'  => $request->user()->id_usuario,
        ]);

        foreach ($request->definiciones as $def) {
            $plantilla->definiciones()->create($def);
        }

        return response()->json(['ok' => true, 'data' => $plantilla->load('definiciones')], 201);
    }

    public function updatePlantilla(Request $request, int $id)
    {
        $plantilla = PlantillaCategoria::findOrFail($id);

        $request->validate([
            'nombre'      => 'sometimes|string|max:100',
            'descripcion' => 'nullable|string',
            'activa'      => 'sometimes|boolean',
        ]);

        $plantilla->update($request->only(['nombre', 'descripcion', 'activa']));

        return response()->json(['ok' => true, 'data' => $plantilla]);
    }

    public function destroyPlantilla(int $id)
    {
        PlantillaCategoria::findOrFail($id)->delete();
        return response()->json(['ok' => true, 'mensaje' => 'Plantilla eliminada']);
    }

    // ── Categorías del torneo ───────────────────────────────────

    public function storeCategoria(Request $request, int $torneoId)
    {
        $torneo = Torneo::findOrFail($torneoId);

        $request->validate([
            'nombre_categoria' => 'required|string|max:150',
            'tipo_disciplina'  => 'required|in:kata,kumite,ambas',
            'sexo'             => 'required|in:masculino,femenino,mixto',
            'edad_min'         => 'nullable|integer|min:0',
            'edad_max'         => 'nullable|integer|min:0',
            'peso_min'         => 'nullable|numeric|min:0',
            'peso_max'         => 'nullable|numeric|min:0',
            'grado_min'        => 'nullable|integer|min:0',
            'grado_max'        => 'nullable|integer|min:0',
            'tatami_asignado'  => 'nullable|integer|min:1',
        ]);

        $categoria = $torneo->categorias()->create($request->all());

        return response()->json(['ok' => true, 'data' => $categoria], 201);
    }

    public function updateCategoria(Request $request, int $torneoId, int $catId)
    {
        $categoria = CategoriaTorneo::where('id_torneo', $torneoId)->findOrFail($catId);

        $request->validate([
            'nombre_categoria' => 'sometimes|string|max:150',
            'tipo_disciplina'  => 'sometimes|in:kata,kumite,ambas',
            'sexo'             => 'sometimes|in:masculino,femenino,mixto',
            'edad_min'         => 'nullable|integer|min:0',
            'edad_max'         => 'nullable|integer|min:0',
            'peso_min'         => 'nullable|numeric|min:0',
            'peso_max'         => 'nullable|numeric|min:0',
            'grado_min'        => 'nullable|integer|min:0',
            'grado_max'        => 'nullable|integer|min:0',
            'tatami_asignado'  => 'nullable|integer|min:1',
            'estado'           => 'sometimes|in:pendiente,en_curso,finalizada',
        ]);

        $categoria->update($request->all());

        return response()->json(['ok' => true, 'data' => $categoria]);
    }

    public function destroyCategoria(int $torneoId, int $catId)
    {
        CategoriaTorneo::where('id_torneo', $torneoId)->findOrFail($catId)->delete();
        return response()->json(['ok' => true, 'mensaje' => 'Categoría eliminada']);
    }

    public function importarPlantilla(Request $request, int $torneoId)
    {
        $torneo = Torneo::findOrFail($torneoId);

        $request->validate([
            'id_plantilla' => 'required|integer|exists:plantilla_categoria,id_plantilla',
        ]);

        $plantilla = PlantillaCategoria::with('definiciones')->findOrFail($request->id_plantilla);

        $torneo->update(['id_plantilla' => $plantilla->id_plantilla]);

        foreach ($plantilla->definiciones as $def) {
            $torneo->categorias()->create([
                'id_categoria_def'   => $def->id_categoria_def,
                'nombre_categoria'   => $def->nombre_categoria,
                'tipo_disciplina'    => $def->tipo_disciplina,
                'sexo'               => $def->sexo,
                'edad_min'           => $def->edad_min,
                'edad_max'           => $def->edad_max,
                'peso_min'           => $def->peso_min,
                'peso_max'           => $def->peso_max,
                'grado_min'          => $def->grado_min,
                'grado_max'          => $def->grado_max,
            ]);
        }

        return response()->json([
            'ok'      => true,
            'mensaje' => "Se importaron " . $plantilla->definiciones->count() . " categorías",
            'data'    => $torneo->load('categorias'),
        ]);
    }
}
