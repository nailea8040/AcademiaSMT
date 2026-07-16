<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\Torneo;
use App\Models\CategoriaTorneo;
use Illuminate\Http\Request;

class InscripcionController extends Controller
{
    public function index(Request $request, int $torneoId)
    {
        $query = Inscripcion::where('id_torneo', $torneoId);

        if ($request->has('categoria')) {
            $query->where('id_categoria_torneo', $request->categoria);
        }

        if ($request->has('dojo_procedencia')) {
            $query->where('dojo_procedencia', $request->dojo_procedencia);
        }

        $inscripciones = $query->orderBy('nombre_completo')->get();

        return response()->json(['ok' => true, 'data' => $inscripciones]);
    }

    public function store(Request $request, int $torneoId)
    {
        $torneo = Torneo::findOrFail($torneoId);

        if ($torneo->estado !== 'inscripcion') {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'El torneo no está en fase de inscripción',
            ], 422);
        }

        $validated = $request->validate([
            'id_categoria_torneo'   => 'required|integer|exists:categoria_torneo,id_categoria_torneo',
            'nombre_completo'       => 'required|string|max:300',
            'fecha_nacimiento'      => 'nullable|date|before:today',
            'genero'                => 'nullable|in:masculino,femenino',
            'grado_cinta'           => 'nullable|string|max:100',
            'peso'                  => 'nullable|numeric|min:0|max:300',
            'dojo_procedencia'      => 'nullable|string|max:200',
            'maestro_cargo'         => 'nullable|string|max:300',
            'disciplina_inscrita'   => 'required|in:kata,kumite,ambas',
        ]);

        $categoria = CategoriaTorneo::findOrFail($validated['id_categoria_torneo']);

        $yaInscrito = Inscripcion::where('id_torneo', $torneoId)
            ->where('id_categoria_torneo', $categoria->id_categoria_torneo)
            ->where('nombre_completo', $validated['nombre_completo'])
            ->where('estado', 'activa')
            ->exists();

        if ($yaInscrito) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Este competidor ya está inscrito en esta categoría',
            ], 422);
        }

        $edad = null;
        if (!empty($validated['fecha_nacimiento'])) {
            $edad = (int) \Carbon\Carbon::parse($validated['fecha_nacimiento'])->age;
        }

        if ($categoria->sexo !== 'mixto' && !empty($validated['genero']) && $categoria->sexo !== $validated['genero']) {
            return response()->json([
                'ok'      => false,
                'mensaje' => "Categoría para sexo {$categoria->sexo}. Competidor registrado como {$validated['genero']}.",
            ], 422);
        }

        if ($edad !== null) {
            if ($categoria->edad_min !== null && $edad < $categoria->edad_min) {
                return response()->json(['ok' => false, 'mensaje' => "Edad mínima: {$categoria->edad_min} años"], 422);
            }
            if ($categoria->edad_max !== null && $edad > $categoria->edad_max) {
                return response()->json(['ok' => false, 'mensaje' => "Edad máxima: {$categoria->edad_max} años"], 422);
            }
        }

        $peso = $validated['peso'] ?? 0;
        if ($peso > 0) {
            if ($categoria->peso_min !== null && $peso < $categoria->peso_min) {
                return response()->json(['ok' => false, 'mensaje' => "Peso mínimo: {$categoria->peso_min} kg"], 422);
            }
            if ($categoria->peso_max !== null && $peso > $categoria->peso_max) {
                return response()->json(['ok' => false, 'mensaje' => "Peso máximo: {$categoria->peso_max} kg"], 422);
            }
        }

        $inscripcion = Inscripcion::create([
            'id_torneo'           => $torneoId,
            'id_categoria_torneo' => $categoria->id_categoria_torneo,
            'nombre_completo'     => $validated['nombre_completo'],
            'fecha_nacimiento'    => $validated['fecha_nacimiento'] ?? null,
            'genero'              => $validated['genero'] ?? null,
            'grado_cinta'         => $validated['grado_cinta'] ?? null,
            'peso'                => $validated['peso'] ?? null,
            'dojo_procedencia'    => $validated['dojo_procedencia'] ?? null,
            'maestro_cargo'       => $validated['maestro_cargo'] ?? null,
            'disciplina_inscrita' => $validated['disciplina_inscrita'],
        ]);

        return response()->json(['ok' => true, 'data' => $inscripcion], 201);
    }

    public function update(Request $request, int $torneoId, int $inscripcionId)
    {
        $inscripcion = Inscripcion::where('id_torneo', $torneoId)->findOrFail($inscripcionId);

        $request->validate([
            'disciplina_inscrita' => 'sometimes|in:kata,kumite,ambas',
            'estado'              => 'sometimes|in:activa,retirada,descalificada',
            'dojo_procedencia'    => 'nullable|string|max:200',
            'maestro_cargo'       => 'nullable|string|max:300',
            'nombre_completo'     => 'sometimes|string|max:300',
            'fecha_nacimiento'    => 'sometimes|date|before:today',
            'genero'              => 'sometimes|in:masculino,femenino',
            'grado_cinta'         => 'sometimes|string|max:100',
            'peso'                => 'sometimes|numeric|min:0|max:300',
        ]);

        $inscripcion->update($request->only([
            'disciplina_inscrita', 'estado', 'dojo_procedencia', 'maestro_cargo',
            'nombre_completo', 'fecha_nacimiento', 'genero', 'grado_cinta', 'peso',
        ]));

        return response()->json(['ok' => true, 'data' => $inscripcion]);
    }

    public function destroy(int $torneoId, int $inscripcionId)
    {
        Inscripcion::where('id_torneo', $torneoId)->findOrFail($inscripcionId)->delete();
        return response()->json(['ok' => true, 'mensaje' => 'Inscripción eliminada']);
    }
}
