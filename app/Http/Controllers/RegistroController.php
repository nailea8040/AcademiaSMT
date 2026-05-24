<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RegistroController extends Controller
{
    public function create()
    {
        $tutores = DB::table('tutor as t')
            ->join('usuario as u', 't.id_Tutor', '=', 'u.id_usuario')
            ->where('u.estado', 1)
            ->select(
                't.id_Tutor',
                DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_completo"),
                't.relacion_estudiante'
            )
            ->get();

        $grados      = DB::table('grado')->orderBy('id_grado', 'asc')->get();
        $ocupaciones = DB::table('ocupacion')->orderBy('nombre_ocupacion', 'asc')->get();
        $alumnos     = DB::table('usuario')
            ->where('rol', 'alumno')->where('estado', 1)
            ->select('id_usuario', DB::raw("CONCAT(nombre,' ',apaterno,' ',amaterno) AS nombre_completo"))
            ->orderBy('nombre')->get();

        return view('usuariosViews.registro', compact('tutores', 'grados', 'ocupaciones', 'alumnos'));
    }

    public function store(Request $request)
    {
        $rol         = $request->input('rol');
        $tutorNuevo  = $request->filled('tutor_nombre');
        $alumnoExtra = $request->filled('alumno_nombre');

        // ── Reglas base ──────────────────────────────────────────────────────
        $rules = [
            'nombre'     => 'required|string|max:100',
            'apaterno'   => 'required|string|max:100',
            'amaterno'   => 'required|string|max:100',
            'fecha_naci' => 'required|date',
            'tel'        => 'required|digits:10',
            'correo'     => 'required|email|unique:usuario,correo',
            'pass'       => 'required|min:8',
            'rol'        => 'required|in:sensei,tutor,alumno',
            'es_bachiller'   => 'nullable|boolean',
            'numero_control' => 'nullable|string|max:20',
            'grupo'          => 'nullable|string|max:10',
            'especialidad'   => 'nullable|string|max:100',
            'turno'          => 'nullable|in:Matutino,Vespertino',
        ];

        if ($rol === 'alumno') {
            $rules['peso']     = 'nullable|numeric|min:0|max:300';
            $rules['estatura'] = 'nullable|numeric|min:0|max:3';
        }

        // ── Reglas tutor ─────────────────────────────────────────────────────
        if ($rol === 'tutor') {
            $rules['ocupacion']           = 'required|integer|exists:ocupacion,id_ocupacion';
            $rules['relacion_estudiante'] = 'required|string|max:50';

            // Alumnos existentes relacionados (tabla tutor_alumno)
            $rules['alumnos']             = 'nullable|array';
            $rules['alumnos.*.id_alumno'] = 'required|exists:usuario,id_usuario';
            $rules['alumnos.*.relacion']  = 'required|string|max:50';

            if ($alumnoExtra) {
                $rules['alumno_nombre']           = 'required|string|max:100';
                $rules['alumno_apaterno']         = 'required|string|max:100';
                $rules['alumno_amaterno']         = 'required|string|max:100';
                $rules['alumno_correo']           = 'required|email|unique:usuario,correo';
                $rules['alumno_pass']             = 'required|min:8';
                $rules['alumno_grado']            = 'required|integer|exists:grado,id_grado';
                $rules['alumno_fecha_inscrip']    = 'required|date';
                $rules['alumno_documento_medico'] = 'required|file|mimes:pdf|max:5120';
                $rules['alumno_peso']             = 'nullable|numeric|min:0|max:300';
                $rules['alumno_estatura']         = 'nullable|numeric|min:0|max:3';
            }
        }

        // ── Reglas alumno ─────────────────────────────────────────────────────
        if ($rol === 'alumno') {
            $rules['grado']            = 'required|integer|exists:grado,id_grado';
            $rules['Fecha_inscrip']    = 'required|date';
            $rules['documento_medico'] = 'required|file|mimes:pdf|max:5120';

            $esMayor = $request->filled('fecha_naci')
                ? \Carbon\Carbon::parse($request->fecha_naci)->age >= 18
                : false;

            if ($tutorNuevo) {
                $rules['tutor_nombre']     = 'required|string|max:100';
                $rules['tutor_apaterno']   = 'required|string|max:100';
                $rules['tutor_amaterno']   = 'required|string|max:100';
                $rules['tutor_fecha_naci'] = 'required|date|before:today';
                $rules['tutor_correo']     = 'required|email|unique:usuario,correo';
                $rules['tutor_tel']        = 'required|digits:10';
                $rules['tutor_ocupacion']  = 'required|integer|exists:ocupacion,id_ocupacion';
                $rules['tutor_pass']       = 'required|min:8';
                $rules['tutor_relacion']   = 'required|string|max:50';
            } else {
                $rules['id_Tutor'] = $esMayor
                    ? 'nullable|exists:tutor,id_Tutor'
                    : 'required|exists:tutor,id_Tutor';
            }
        }

        $validated     = $request->validate($rules);
        $fechaRegistro = now()->toDateString();

        DB::beginTransaction();

        try {
            $idTutorFinal = null;

            // ── Caso D: alumno + tutor NUEVO ─────────────────────────────────
            if ($rol === 'alumno' && $tutorNuevo) {
                $idTutorUsr = DB::table('usuario')->insertGetId([
                    'nombre'         => $validated['tutor_nombre'],
                    'apaterno'       => $validated['tutor_apaterno'],
                    'amaterno'       => $validated['tutor_amaterno'],
                    'fecha_naci'     => $validated['tutor_fecha_naci'],
                    'telefono'       => $validated['tutor_tel'],
                    'correo'         => $validated['tutor_correo'],
                    'pass'           => Hash::make($validated['tutor_pass']),
                    'rol'            => 'tutor',
                    'fecha_registro' => $fechaRegistro,
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

            // ── Insertar usuario principal ────────────────────────────────────
            $esBachiller = $request->boolean('es_bachiller');

            $idUsuario = DB::table('usuario')->insertGetId([
                'nombre'         => $validated['nombre'],
                'apaterno'       => $validated['apaterno'],
                'amaterno'       => $validated['amaterno'],
                'fecha_naci'     => $validated['fecha_naci'],
                'telefono'       => $validated['tel'],
                'correo'         => $validated['correo'],
                'pass'           => Hash::make($validated['pass']),
                'rol'            => $validated['rol'],
                'fecha_registro' => $fechaRegistro,
                'estado'         => 1,
                'numero_control' => $esBachiller ? ($validated['numero_control'] ?? null) : null,
                'grupo'          => $esBachiller ? ($validated['grupo']          ?? null) : null,
                'especialidad'   => $esBachiller ? ($validated['especialidad']   ?? null) : null,
                'turno'          => $esBachiller ? ($validated['turno']          ?? null) : null,
            ]);

            // ── Caso B: tutor ─────────────────────────────────────────────────
            if ($rol === 'tutor') {
                DB::table('tutor')->insert([
                    'id_Tutor'            => $idUsuario,
                    'id_ocupacion'        => $validated['ocupacion'],
                    'relacion_estudiante' => $validated['relacion_estudiante'],
                ]);

                // Vincular alumnos EXISTENTES seleccionados en el builder
                if (!empty($validated['alumnos'])) {
                    $rows = [];
                    foreach ($validated['alumnos'] as $a) {
                        $rows[] = [
                            'id_tutor'   => $idUsuario,
                            'id_alumno'  => $a['id_alumno'],
                            'relacion'   => $a['relacion'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    DB::table('tutor_alumno')->insert($rows);
                }

                // Caso B2: tutor + alumno NUEVO extra
                if ($alumnoExtra) {
                    $idAlumno = DB::table('usuario')->insertGetId([
                        'nombre'         => $validated['alumno_nombre'],
                        'apaterno'       => $validated['alumno_apaterno'],
                        'amaterno'       => $validated['alumno_amaterno'],
                        'fecha_naci'     => now()->subYears(10)->toDateString(),
                        'telefono'       => $validated['tel'],
                        'correo'         => $validated['alumno_correo'],
                        'pass'           => Hash::make($validated['alumno_pass']),
                        'rol'            => 'alumno',
                        'fecha_registro' => $fechaRegistro,
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

                    // Vincular el alumno nuevo al tutor en tutor_alumno
                    DB::table('tutor_alumno')->insert([
                        'id_tutor'   => $idUsuario,
                        'id_alumno'  => $idAlumno,
                        'relacion'   => $validated['relacion_estudiante'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // ── Caso C/D: alumno ──────────────────────────────────────────────
            if ($rol === 'alumno') {
                $obsGrado = 'Grado inicial al momento de inscripción.';
                if ($idTutorFinal) {
                    $tutor = DB::table('usuario')->where('id_usuario', $idTutorFinal)->first();
                    if ($tutor) $obsGrado .= ' Tutor: ' . $tutor->nombre . ' ' . $tutor->apaterno;
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

                // Vincular alumno ↔ tutor en tutor_alumno
                if ($idTutorFinal) {
                    $relacionTutor = $tutorNuevo
                        ? $validated['tutor_relacion']
                        : DB::table('tutor')->where('id_Tutor', $idTutorFinal)->value('relacion_estudiante');

                    DB::table('tutor_alumno')->insert([
                        'id_tutor'   => $idTutorFinal,
                        'id_alumno'  => $idUsuario,
                        'relacion'   => $relacionTutor ?? 'Tutor',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('login')
                ->with('status', '¡Registro exitoso! Ya puedes iniciar sesión.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RegistroController@store ERROR: ' . $e->getMessage()
                . ' | File: ' . $e->getFile()
                . ' | Line: ' . $e->getLine());
            return redirect()->back()->withInput()
                ->withErrors(['registro_error' => '[Línea ' . $e->getLine() . '] ' . $e->getMessage()]);
        }
    }
}