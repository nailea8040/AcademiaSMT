<?php
// app/Http/Controllers/PrincipalController.php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PrincipalController extends Controller
{
    public function index()
    {
        try {
            // ── 1. KPIs del hero ─────────────────────────────────────────────────
            $totalAlumnos    = DB::table('usuario')->where('rol', 'alumno')->where('estado', 1)->count();
            $totalMaestros   = DB::table('usuario')->whereIn('rol', ['sensei', 'tutor'])->where('estado', 1)->count();
            $mesesTrayectoria = 15; // Ajusta este valor manualmente o calcúlalo desde otra tabla

            // ── 2. Ingresos por mes (últimos 12 meses) ───────────────────────────
            // Suma de monto_pagado de pagos Completados, agrupado por mes
            $ingresosPorMes = DB::select("
                SELECT
                    DATE_FORMAT(fecha_pago, '%Y-%m') AS mes,
                    DATE_FORMAT(fecha_pago, '%b %Y')  AS mes_label,
                    SUM(COALESCE(monto_pagado, 0))    AS total_ingresos,
                    COUNT(*)                           AS total_pagos
                FROM pago
                WHERE estado_pago = 'Completado'
                  AND fecha_pago >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY mes, mes_label
                ORDER BY mes ASC
            ");

            // ── 3. Nuevos alumnos por mes (últimos 12 meses) ─────────────────────
            $alumnosPorMes = DB::select("
                SELECT
                    DATE_FORMAT(fecha_registro, '%Y-%m') AS mes,
                    DATE_FORMAT(fecha_registro, '%b %Y')  AS mes_label,
                    COUNT(*) AS total_alumnos
                FROM usuario
                WHERE rol = 'alumno'
                  AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY mes, mes_label
                ORDER BY mes ASC
            ");

            // ── 4. Alumnos por grado (grado actual = último en historial_grados) ─
            $alumnosPorGrado = DB::select("
                SELECT
                    g.nombreGrado AS grado,
                    g.orden,
                    COUNT(DISTINCT hg.id_usuario) AS total_alumnos
                FROM historial_grados hg
                INNER JOIN (
                    SELECT id_usuario, MAX(fecha_obtencion) AS ultima_fecha
                    FROM historial_grados
                    GROUP BY id_usuario
                ) ult ON hg.id_usuario = ult.id_usuario
                       AND hg.fecha_obtencion = ult.ultima_fecha
                INNER JOIN grado g ON hg.id_grado = g.id_grado
                INNER JOIN usuario u ON hg.id_usuario = u.id_usuario
                WHERE u.rol = 'alumno' AND u.estado = 1
                GROUP BY g.id_grado, g.nombreGrado, g.orden
                ORDER BY g.id_grado ASC
            ");

            // ── 5. Alumnos sin avance de grado en 12+ meses ──────────────────────
            $alumnosEstancados = DB::select("
                SELECT
                    u.id_usuario,
                    CONCAT(u.nombre, ' ', u.apaterno, ' ', u.amaterno) AS nombre_completo,
                    g.nombreGrado AS grado_actual,
                    hg.fecha_obtencion,
                    TIMESTAMPDIFF(MONTH, hg.fecha_obtencion, NOW()) AS meses_en_grado
                FROM historial_grados hg
                INNER JOIN (
                    SELECT id_usuario, MAX(fecha_obtencion) AS ultima_fecha
                    FROM historial_grados
                    GROUP BY id_usuario
                ) ult ON hg.id_usuario = ult.id_usuario
                       AND hg.fecha_obtencion = ult.ultima_fecha
                INNER JOIN grado g  ON hg.id_grado    = g.id_grado
                INNER JOIN usuario u ON hg.id_usuario = u.id_usuario
                WHERE u.rol = 'alumno'
                  AND u.estado = 1
                  AND TIMESTAMPDIFF(MONTH, hg.fecha_obtencion, NOW()) >= 12
                ORDER BY meses_en_grado DESC
            ");

            // ── 6. Pagos pendientes (resumen para KPI) ───────────────────────────
            $pagosPendientes = DB::table('pago')
                ->where('estado_pago', 'Pendiente')
                ->count();

            $montoPendiente = DB::table('pago')
                ->where('estado_pago', 'Pendiente')
                ->sum(DB::raw('COALESCE(monto_total, monto) - COALESCE(monto_pagado, 0)'));

            // ── 7. Ingresos por concepto (para pie chart) ────────────────────────
            $ingresosPorConcepto = DB::select("
                SELECT
                    COALESCE(cp.nombre, p.motivo_pago, 'Sin concepto') AS concepto,
                    SUM(COALESCE(p.monto_pagado, 0)) AS total
                FROM pago p
                LEFT JOIN concepto_pago cp ON p.id_concepto = cp.id_concepto
                WHERE p.estado_pago = 'Completado'
                GROUP BY concepto
                ORDER BY total DESC
                LIMIT 8
            ");

            return view('usuariosViews.principal', compact(
                'totalAlumnos',
                'totalMaestros',
                'mesesTrayectoria',
                'ingresosPorMes',
                'alumnosPorMes',
                'alumnosPorGrado',
                'alumnosEstancados',
                'pagosPendientes',
                'montoPendiente',
                'ingresosPorConcepto'
            ));

        } catch (\Exception $e) {
            Log::error('PrincipalController@index: ' . $e->getMessage());

            // Fallback seguro — la vista sigue funcionando con datos vacíos
            return view('usuariosViews.principal', [
                'totalAlumnos'        => 0,
                'totalMaestros'       => 0,
                'mesesTrayectoria'    => 15,
                'ingresosPorMes'      => [],
                'alumnosPorMes'       => [],
                'alumnosPorGrado'     => [],
                'alumnosEstancados'   => [],
                'pagosPendientes'     => 0,
                'montoPendiente'      => 0,
                'ingresosPorConcepto' => [],
            ]);
        }
    }
}