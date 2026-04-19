<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * ══════════════════════════════════════════════════════════════
 *  FLUJOS DE REGISTRO (sin columnas extra en tabla usuario):
 *
 *  A) sensei  → solo INSERT en usuario
 *  B) tutor   → INSERT usuario + INSERT tutor (+ ocupacion)
 *  B2) tutor + alumno extra → B + INSERT usuario(alumno)
 *                              + INSERT historial_grados
 *                              + INSERT registro_fisico
 *  C) alumno con tutor existente → INSERT usuario(alumno)
 *                                  + INSERT historial_grados
 *                                  + INSERT registro_fisico
 *  D) alumno con tutor nuevo → INSERT usuario(tutor) + INSERT tutor
 *                              + INSERT usuario(alumno)
 *                              + INSERT historial_grados
 *                              + INSERT registro_fisico
 *
 *  Relación alumno-tutor:
 *    - Se registra en historial_grados con observaciones
 *    - El tutor se identifica por rol='tutor' + registro en tabla tutor
 *    - No se agrega columna id_tutor_responsable a usuario
 * ══════════════════════════════════════════════════════════════
 */
class RegistroController extends Controller
{
    public function create()
    {
        // Tutores: usuarios con rol='tutor' que tienen registro en tabla tutor
        $tutores = DB::table('tutor as t')
            ->join('usuario as u', 't.id_Tutor', '=', 'u.id_usuario')
            ->where('u.estado', 1)
            ->select(
                't.id_Tutor',
                DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_completo"),
                't.relacion_estudiante'
            )
            ->get();

        $grados     = DB::table('grado')->orderBy('id_grado', 'asc')->get();
        $ocupaciones = DB::table('ocupacion')->orderBy('nombre_ocupacion', 'asc')->get();

        return view('usuariosViews.registro', compact('tutores', 'grados', 'ocupaciones'));
    }

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
            // Campo form 'tel' → columna BD 'telefono'
            'tel'            => 'required|digits:10',
            'correo'         => 'required|email|unique:usuario,correo',
            'pass'           => 'required|min:8',
            'rol'            => 'required|in:sensei,tutor,alumno',
            'fecha_registro' => 'required|date',
        ];

        // ── Reglas adicionales para tutor ───────────────────────────
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
            }
        }

        // ── Reglas adicionales para alumno ───────────────────────────
        if ($rol === 'alumno') {
            $rules['grado']            = 'required|integer|exists:grado,id_grado';
            $rules['Fecha_inscrip']    = 'required|date';
            $rules['documento_medico'] = 'required|file|mimes:pdf|max:5120';

            // Calcular si el alumno es mayor de edad
            $esMayor = false;
            if ($request->filled('fecha_naci')) {
                $esMayor = \Carbon\Carbon::parse($request->fecha_naci)->age >= 18;
            }

            if ($tutorNuevo) {
                $rules['tutor_nombre']    = 'required|string|max:100';
                $rules['tutor_apaterno']  = 'required|string|max:100';
                $rules['tutor_amaterno']  = 'required|string|max:100';
                $rules['tutor_correo']    = 'required|email|unique:usuario,correo';
                $rules['tutor_tel']       = 'required|digits:10';
                $rules['tutor_ocupacion'] = 'required|integer|exists:ocupacion,id_ocupacion';
                $rules['tutor_pass']      = 'required|min:8';
                $rules['tutor_relacion']  = 'required|string|max:50';
            } else {
                // Tutor existente: obligatorio si menor de edad, opcional si mayor
                $rules['id_Tutor'] = $esMayor
                    ? 'nullable|exists:tutor,id_Tutor'
                    : 'required|exists:tutor,id_Tutor';
            }
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            $idTutorFinal = null;

            // ── CASO D: alumno con tutor NUEVO ───────────────────────
            if ($rol === 'alumno' && $tutorNuevo) {

                $idTutorUsr = DB::table('usuario')->insertGetId([
                    'nombre'         => $validated['tutor_nombre'],
                    'apaterno'       => $validated['tutor_apaterno'],
                    'amaterno'       => $validated['tutor_amaterno'],
                    'fecha_naci'     => now()->subYears(30)->toDateString(),
                    'telefono'       => $validated['tutor_tel'],   // columna real en BD
                    'correo'         => $validated['tutor_correo'],
                    'pass'           => Hash::make($validated['tutor_pass']),
                    'rol'            => 'tutor',
                    'fecha_registro' => $validated['fecha_registro'],
                    'estado'         => 1,
                ]);

                DB::table('tutor')->insert([
                    'id_Tutor'            => $idTutorUsr,
                    'id_ocupacion'        => $validated['tutor_ocupacion'], // ya es id del catálogo
                    'relacion_estudiante' => $validated['tutor_relacion'],
                ]);

                $idTutorFinal = $idTutorUsr;

            } elseif ($rol === 'alumno') {
                // Para mayor de edad id_Tutor puede venir vacío
                $idTutorFinal = !empty($validated['id_Tutor']) ? $validated['id_Tutor'] : null;
            }

            // ── Insertar usuario principal ───────────────────────────
            $idUsuario = DB::table('usuario')->insertGetId([
                'nombre'         => $validated['nombre'],
                'apaterno'       => $validated['apaterno'],
                'amaterno'       => $validated['amaterno'],
                'fecha_naci'     => $validated['fecha_naci'],
                'telefono'       => $validated['tel'],   // columna real en BD
                'correo'         => $validated['correo'],
                'pass'           => Hash::make($validated['pass']),
                'rol'            => $validated['rol'],
                'fecha_registro' => $validated['fecha_registro'],
                'estado'         => 1,
            ]);

            // ── CASO B: tutor ────────────────────────────────────────
            if ($rol === 'tutor') {

                DB::table('tutor')->insert([
                    'id_Tutor'            => $idUsuario,
                    'id_ocupacion'        => $validated['ocupacion'], // id del catálogo
                    'relacion_estudiante' => $validated['relacion_estudiante'],
                ]);

                // ── CASO B2: tutor + alumno extra ────────────────────
                if ($alumnoExtra) {
                    $partes   = explode(' ', trim($validated['alumno_nombre']), 3);
                    $aNombre  = $partes[0] ?? '-';
                    $aPaterno = $partes[1] ?? '-';
                    $aMaterno = $partes[2] ?? '';

                    $idAlumno = DB::table('usuario')->insertGetId([
                        'nombre'         => $aNombre,
                        'apaterno'       => $aPaterno,
                        'amaterno'       => $aMaterno,
                        'fecha_naci'     => now()->subYears(10)->toDateString(),
                        'telefono'       => $validated['tel'],
                        'correo'         => $validated['alumno_correo'],
                        'pass'           => Hash::make($validated['alumno_pass']),
                        'rol'            => 'alumno',
                        'fecha_registro' => $validated['fecha_registro'],
                        'estado'         => 1,
                    ]);

                    // Grado inicial en historial_grados (no columna en usuario)
                    DB::table('historial_grados')->insert([
                        'id_usuario'      => $idAlumno,
                        'id_grado'        => $validated['alumno_grado'],
                        'fecha_obtencion' => $validated['alumno_fecha_inscrip'],
                        'observaciones'   => 'Grado inicial. Tutor registrado: ' . $validated['nombre'] . ' ' . $validated['apaterno'],
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
                        'peso'               => 0,
                        'estatura'           => 0,
                        'certificado_medico' => $rutaDoc,
                        'fecha_registro'     => $validated['alumno_fecha_inscrip'],
                    ]);
                }
            }

            // ── CASO C / D: alumno ───────────────────────────────────
            if ($rol === 'alumno') {

                // Grado inicial en historial_grados (no columna en usuario)
                $obsGrado = 'Grado inicial al momento de inscripción.';
                if ($idTutorFinal) {
                    $tutor = DB::table('usuario')->where('id_usuario', $idTutorFinal)->first();
                    if ($tutor) {
                        $obsGrado .= ' Tutor: ' . $tutor->nombre . ' ' . $tutor->apaterno;
                    }
                } else {
                    $obsGrado .= ' Alumno mayor de edad, sin tutor asignado.';
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
                    'peso'               => 0,
                    'estatura'           => 0,
                    'certificado_medico' => $rutaDoc,
                    'fecha_registro'     => $validated['Fecha_inscrip'],
                ]);
            }

            DB::commit();

            $msg = '¡Registro exitoso! Ya puedes iniciar sesión.';

            return redirect()->route('verLogin')->with('status', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RegistroController@store: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->withErrors(['registro_error' => 'Error al registrar: ' . $e->getMessage()]);
        }
    }
}