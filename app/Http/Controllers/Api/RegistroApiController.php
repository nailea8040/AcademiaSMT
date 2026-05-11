<?php

namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class RegistroApiController extends Controller
{
    /**
     * POST /api/registro
     * Acepta multipart/form-data (puede incluir PDF del documento médico)
     *
     * Flujos soportados (igual que RegistroController web):
     *   A) rol=sensei
     *   B) rol=tutor  (+ alumno extra opcional)
     *   C) rol=alumno con id_Tutor existente
     *   D) rol=alumno con tutor nuevo (campos tutor_*)
     */
    public function store(Request $request)
    {
        $rol        = $request->input('rol');
        $tutorNuevo = $request->filled('tutor_nombre');
        $alumnoExtra = $request->filled('alumno_nombre');
 
        // ── Reglas base ──────────────────────────────────────────────
        $rules = [
            'nombre'         => 'required|string|max:100',
            'apaterno'       => 'required|string|max:100',
            'amaterno'       => 'required|string|max:100',
            'fecha_naci'     => 'required|date',
            'tel'            => 'required|digits:10',
            'correo'         => 'required|email|unique:usuario,correo',
            'pass'           => 'required|min:8',
            'rol'            => 'required|in:sensei,tutor,alumno',
            // fecha_registro generada en el servidor
            // Bachiller — opcionales para cualquier rol (RegistroController web los acepta globalmente)
            'es_bachiller'   => 'nullable|boolean',
            'numero_control' => 'nullable|string|max:20',
            'grupo'          => 'nullable|string|max:10',
            'especialidad'   => 'nullable|string|max:100',
            'turno'          => 'nullable|in:Matutino,Vespertino',
        ];
 
        if ($rol === 'tutor') {
            $rules['ocupacion']           = 'required|integer|exists:ocupacion,id_ocupacion';
            $rules['relacion_estudiante'] = 'required|string|max:50';
 
            if ($alumnoExtra) {
                $rules['alumno_nombre']           = 'required|string|max:200';
                $rules['alumno_correo']           = 'required|email|unique:usuario,correo';
                $rules['alumno_pass']             = 'required|min:8';
                $rules['alumno_grado']            = 'required|integer|exists:grado,id_grado';
                $rules['alumno_fecha_inscrip']    = 'required|date';
                $rules['alumno_documento_medico'] = 'required|file|mimes:pdf|max:5120';
                // Físicos del alumno extra — RegistroController web los acepta
                $rules['alumno_peso']             = 'nullable|numeric|min:0|max:300';
                $rules['alumno_estatura']         = 'nullable|numeric|min:0|max:3';
            }
        }
 
        if ($rol === 'alumno') {
            $rules['grado']            = 'required|integer|exists:grado,id_grado';
            $rules['Fecha_inscrip']    = 'required|date';
            $rules['documento_medico'] = 'required|file|mimes:pdf|max:5120';
            // Registro físico — opcionales igual que RegistroController web
            $rules['peso']             = 'nullable|numeric|min:0|max:300';
            $rules['estatura']         = 'nullable|numeric|min:0|max:3';
 
            $esMayor = $request->filled('fecha_naci')
                ? \Carbon\Carbon::parse($request->fecha_naci)->age >= 18
                : false;
 
            if ($tutorNuevo) {
                $rules['tutor_nombre']    = 'required|string|max:100';
                $rules['tutor_apaterno']  = 'required|string|max:100';
                $rules['tutor_amaterno']  = 'required|string|max:100';
                // tutor_fecha_naci agregado — RegistroController web lo valida y lo guarda
                $rules['tutor_fecha_naci'] = 'required|date|before:today';
                $rules['tutor_correo']    = 'required|email|unique:usuario,correo';
                $rules['tutor_tel']       = 'required|digits:10';
                $rules['tutor_ocupacion'] = 'required|integer|exists:ocupacion,id_ocupacion';
                $rules['tutor_pass']      = 'required|min:8';
                $rules['tutor_relacion']  = 'required|string|max:50';
            } else {
                $rules['id_Tutor'] = $esMayor
                    ? 'nullable|exists:tutor,id_Tutor'
                    : 'required|exists:tutor,id_Tutor';
            }
        }
 
        $validated = $request->validate($rules);
 
        // Igual que RegistroController web: fecha_registro se genera en el servidor
        $fechaRegistro = now()->toDateString();

        DB::beginTransaction();
 
        try {
            $idTutorFinal = null;
 
            // ── Caso D: alumno + tutor nuevo ─────────────────────────
            if ($rol === 'alumno' && $tutorNuevo) {
                $idTutorUsr = DB::table('usuario')->insertGetId([
                    'nombre'         => $validated['tutor_nombre'],
                    'apaterno'       => $validated['tutor_apaterno'],
                    'amaterno'       => $validated['tutor_amaterno'],
                    // Usar fecha_naci real del tutor — RegistroController web también la usa
                    'fecha_naci'     => $validated['tutor_fecha_naci'] ?? now()->subYears(30)->toDateString(),
                    'telefono'       => $validated['tutor_tel'],
                    'correo'         => $validated['tutor_correo'],
                    'pass'           => Hash::make($validated['tutor_pass']),
                    'rol'            => 'tutor',
                    'fecha_registro' => $fechaRegistro,  // generada en servidor
                    'estado'         => 1,
                ]);
                DB::table('tutor')->insert([
                    'id_Tutor'            => $idTutorUsr,
                    'id_ocupacion'        => $validated['tutor_ocupacion'],
                    'relacion_estudiante' => $validated['tutor_relacion'],
                ]);
                $idTutorFinal = $idTutorUsr;
 
            } elseif ($rol === 'alumno') {
                $idTutorFinal = !empty($validated['id_Tutor']) ? $validated['id_Tutor'] : null;
            }
 
            // ── Insertar usuario principal ───────────────────────────
            $esBachiller = filter_var($request->input('es_bachiller', false), FILTER_VALIDATE_BOOLEAN);

            $idUsuario = DB::table('usuario')->insertGetId([
                'nombre'          => $validated['nombre'],
                'apaterno'        => $validated['apaterno'],
                'amaterno'        => $validated['amaterno'],
                'fecha_naci'      => $validated['fecha_naci'],
                'telefono'        => $validated['tel'],
                'correo'          => $validated['correo'],
                'pass'            => Hash::make($validated['pass']),
                'rol'             => $validated['rol'],
                'fecha_registro'  => $fechaRegistro,
                'estado'          => 1,
                // Bachiller — igual que RegistroController web
                'numero_control'  => $esBachiller ? ($validated['numero_control'] ?? null) : null,
                'grupo'           => $esBachiller ? ($validated['grupo']          ?? null) : null,
                'especialidad'    => $esBachiller ? ($validated['especialidad']   ?? null) : null,
                'turno'           => $esBachiller ? ($validated['turno']          ?? null) : null,
            ]);
 
            // ── Caso B: tutor ────────────────────────────────────────
            if ($rol === 'tutor') {
                DB::table('tutor')->insert([
                    'id_Tutor'            => $idUsuario,
                    'id_ocupacion'        => $validated['ocupacion'],
                    'relacion_estudiante' => $validated['relacion_estudiante'],
                ]);
 
                // Caso B2: tutor + alumno extra
                if ($alumnoExtra) {
                    // NOTA: el web espera alumno_nombre/alumno_apaterno/alumno_amaterno separados.
                    // La API acepta alumno_nombre como nombre completo y hace explode().
                    $partes   = explode(' ', trim($validated['alumno_nombre']), 3);
                    $idAlumno = DB::table('usuario')->insertGetId([
                        'nombre'         => $partes[0] ?? '-',
                        'apaterno'       => $partes[1] ?? '-',
                        'amaterno'       => $partes[2] ?? '',
                        'fecha_naci'     => now()->subYears(10)->toDateString(),
                        'telefono'       => $validated['tel'],
                        'correo'         => $validated['alumno_correo'],
                        'pass'           => Hash::make($validated['alumno_pass']),
                        'rol'            => 'alumno',
                        'fecha_registro' => $fechaRegistro,  // generada en servidor
                        'estado'         => 1,
                    ]);
 
                    DB::table('historial_grados')->insert([
                        'id_usuario'      => $idAlumno,
                        'id_grado'        => $validated['alumno_grado'],
                        'fecha_obtencion' => $validated['alumno_fecha_inscrip'],
                        'observaciones'   => 'Grado inicial. Tutor: ' . $validated['nombre'] . ' ' . $validated['apaterno'],
                    ]);
 
                    $rutaDoc = null;
                    if ($request->hasFile('alumno_documento_medico')) {
                        $rutaDoc = $request->file('alumno_documento_medico')->storeAs(
                            'documentos_medicos',
                            'medico_' . $idAlumno . '_' . time() . '.pdf',
                            'public'
                        );
                    }
 
                    DB::table('registro_fisico')->insert([
                        'id_usuario'         => $idAlumno,
                        'peso'               => !empty($validated['alumno_peso'])     ? $validated['alumno_peso']     : 0,
                        'estatura'           => !empty($validated['alumno_estatura']) ? $validated['alumno_estatura'] : 0,
                        'certificado_medico' => $rutaDoc,
                        'fecha_registro'     => $validated['alumno_fecha_inscrip'],
                    ]);
                }
            }
 
            // ── Caso C/D: alumno ─────────────────────────────────────
            if ($rol === 'alumno') {
                $obsGrado = 'Grado inicial al momento de inscripción.';
                if ($idTutorFinal) {
                    $tutor = DB::table('usuario')->where('id_usuario', $idTutorFinal)->first();
                    if ($tutor) {
                        $obsGrado .= ' Tutor: ' . $tutor->nombre . ' ' . $tutor->apaterno;
                    }
                } else {
                    $obsGrado .= ' Alumno mayor de edad, sin tutor.';
                }
 
                DB::table('historial_grados')->insert([
                    'id_usuario'      => $idUsuario,
                    'id_grado'        => $validated['grado'],
                    'fecha_obtencion' => $validated['Fecha_inscrip'],
                    'observaciones'   => $obsGrado,
                ]);
 
                $rutaDoc = null;
                if ($request->hasFile('documento_medico')) {
                    $rutaDoc = $request->file('documento_medico')->storeAs(
                        'documentos_medicos',
                        'medico_' . $idUsuario . '_' . time() . '.pdf',
                        'public'
                    );
                }
 
                DB::table('registro_fisico')->insert([
                    'id_usuario'         => $idUsuario,
                    'peso'               => !empty($validated['peso'])     ? $validated['peso']     : 0,
                    'estatura'           => !empty($validated['estatura']) ? $validated['estatura'] : 0,
                    'certificado_medico' => $rutaDoc,
                    'fecha_registro'     => $validated['Fecha_inscrip'],
                ]);
            }
 
            DB::commit();
 
            return response()->json([
                'success'    => true,
                'message'    => 'Registro exitoso.',
                'id_usuario' => $idUsuario,
            ], 201);
 
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RegistroApi@store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar: ' . $e->getMessage(),
            ], 500);
        }
    }
}