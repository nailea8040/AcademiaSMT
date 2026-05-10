<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * AsistenciaController  —  panel web
 *
 * Vista:   asistencia.blade.php
 * Rutas:
 *   GET  /asistencia              → vista con selector de fecha
 *   GET  /asistencia/pdf?fecha=   → descarga PDF del día
 *   GET  /asistencia/excel?fecha= → descarga Excel del día
 *
 * Tabla asistencia: id_asistencia, id_usuario, fecha, token, registrado_por
 * Tabla usuario:    nombre, apaterno, amaterno, numero_control,
 *                   grupo, especialidad, turno  (columnas nuevas)
 */
class AsistenciaController extends Controller
{
    // ── Vista principal ───────────────────────────────────────────────────

    public function index(Request $request)
    {
        $fecha = $request->get('fecha', now()->toDateString());

        try {
            $asistencias = $this->obtenerAsistencias($fecha);

            return view('usuariosViews.asistencia', compact('asistencias', 'fecha'));

        } catch (\Exception $e) {
            Log::error('AsistenciaController@index: ' . $e->getMessage());
            return view('usuariosViews.asistencia', ['asistencias' => collect(), 'fecha' => $fecha])
                ->with('error', 'Error al cargar las asistencias.');
        }
    }

    // ── Descargar PDF ─────────────────────────────────────────────────────

    public function descargarPdf(Request $request)
    {
        $fecha  = $request->get('fecha', now()->toDateString());
        $filtro = $request->get('filtro', 'todos'); // 'todos' | 'bachiller'

        try {
            $asistencias = $this->obtenerAsistencias($fecha);

            if ($filtro === 'bachiller') {
                $asistencias = $asistencias->filter(fn($a) => !is_null($a->numero_control))->values();
            }
            $fechaFormato = \Carbon\Carbon::parse($fecha)->format('d/m/Y');

            // Generar HTML del PDF
            $html = $this->generarHtml($asistencias, $fecha, $fechaFormato, $filtro);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
                ->setPaper('letter', 'portrait');

            $sufijo = $filtro === 'bachiller' ? '_bachiller' : '';
            $nombreArchivo = 'asistencia_' . str_replace('-', '', $fecha) . $sufijo . '.pdf';

            return $pdf->download($nombreArchivo);

        } catch (\Exception $e) {
            Log::error('AsistenciaController@descargarPdf: ' . $e->getMessage());
            return back()->with('error', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }

    // ── Descargar Excel ───────────────────────────────────────────────────

    public function descargarExcel(Request $request)
    {
        $fecha  = $request->get('fecha', now()->toDateString());
        $filtro = $request->get('filtro', 'todos'); // 'todos' | 'bachiller'

        try {
            $asistencias = $this->obtenerAsistencias($fecha);

            if ($filtro === 'bachiller') {
                $asistencias = $asistencias->filter(fn($a) => !is_null($a->numero_control))->values();
            }

            $fechaFormato = \Carbon\Carbon::parse($fecha)->format('d/m/Y');

            $sufijo        = $filtro === 'bachiller' ? '_bachiller' : '';
            $nombreArchivo = 'asistencia_' . str_replace('-', '', $fecha) . $sufijo . '.csv';

            $headers = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '"',
            ];

            $callback = function () use ($asistencias, $fecha, $fechaFormato, $filtro) {
                $output = fopen('php://output', 'w');

                // BOM UTF-8 para compatibilidad con Excel
                fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

                // Título
                $tituloFiltro = $filtro === 'bachiller' ? ' (Solo Bachiller)' : '';
                fputcsv($output, ['Academia de Karate-Do SMT — Asistencia del ' . $fechaFormato . $tituloFiltro]);
                fputcsv($output, []);

                // Encabezados
                fputcsv($output, [
                    'N°',
                    'Nombre Completo',
                    'Número de Control',
                    'Grupo',
                    'Especialidad',
                    'Turno',
                    'Hora de Registro',
                    'Es Bachiller',
                ]);

                // Datos
                $contador = 1;
                foreach ($asistencias as $a) {
                    fputcsv($output, [
                        $contador++,
                        trim($a->nombre_completo),
                        $a->numero_control ?? '—',
                        $a->grupo          ?? '—',
                        $a->especialidad   ?? '—',
                        $a->turno          ?? '—',
                        $a->hora_registro,
                        $a->numero_control ? 'Sí' : 'No',
                    ]);
                }

                // Totales
                fputcsv($output, []);
                fputcsv($output, ['Total de asistencias:', $asistencias->count()]);
                $bachilleres = $asistencias->filter(fn($a) => $a->numero_control)->count();
                fputcsv($output, ['Bachilleres:', $bachilleres]);
                fputcsv($output, ['Otros alumnos:', $asistencias->count() - $bachilleres]);

                fclose($output);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('AsistenciaController@descargarExcel: ' . $e->getMessage());
            return back()->with('error', 'Error al generar el Excel: ' . $e->getMessage());
        }
    }

    // ── Helpers privados ──────────────────────────────────────────────────

    /**
     * Obtiene las asistencias de un día con todos los datos del alumno
     */
    private function obtenerAsistencias(string $fecha)
    {
        return DB::table('asistencia as a')
            ->join('usuario as u', 'a.id_usuario', '=', 'u.id_usuario')
            ->whereDate('a.fecha', $fecha)
            ->select(
                'a.id_asistencia',
                'a.fecha',
                DB::raw("TIME(a.fecha) AS hora_registro"),
                DB::raw("CONCAT(u.nombre,' ',u.apaterno,' ',COALESCE(u.amaterno,''))
                         AS nombre_completo"),
                'u.numero_control',
                'u.grupo',
                'u.especialidad',
                'u.turno',
                'u.rol'
            )
            ->orderBy('a.fecha', 'asc')
            ->get();
    }

    /**
     * Genera el HTML del reporte para DomPDF
     */
    private function generarHtml($asistencias, string $fecha, string $fechaFormato, string $filtro = 'todos'): string
    {
        $totalBachilleres = $asistencias->filter(fn($a) => $a->numero_control)->count();
        $totalOtros       = $asistencias->count() - $totalBachilleres;
        $tituloFiltro     = $filtro === 'bachiller' ? ' — Solo Bachiller' : '';

        $filas = '';
        $i = 1;
        foreach ($asistencias as $a) {
            $esBachiller = $a->numero_control ? 'Sí' : 'No';
            $bgColor     = $i % 2 === 0 ? '#f9f9f9' : '#ffffff';
            $filas .= "
            <tr style='background:{$bgColor}'>
                <td>{$i}</td>
                <td>" . htmlspecialchars(trim($a->nombre_completo)) . "</td>
                <td>" . ($a->numero_control ?? '—') . "</td>
                <td>" . ($a->grupo          ?? '—') . "</td>
                <td>" . ($a->especialidad   ?? '—') . "</td>
                <td>" . ($a->turno          ?? '—') . "</td>
                <td>" . $a->hora_registro   . "</td>
                <td style='text-align:center;color:" . ($a->numero_control ? '#2e7d32' : '#757575') . ";font-weight:bold'>{$esBachiller}</td>
            </tr>";
            $i++;
        }

        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body        { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 20px; }
                h1          { color: #e53935; font-size: 16px; margin-bottom: 4px; }
                h2          { color: #555; font-size: 13px; font-weight: normal; margin-bottom: 16px; }
                table       { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th          { background: #e53935; color: white; padding: 7px 6px; text-align: left; font-size: 10px; }
                td          { padding: 6px; border-bottom: 1px solid #e0e0e0; font-size: 10px; }
                .resumen    { margin-top: 16px; font-size: 11px; color: #555; }
                .resumen span { font-weight: bold; color: #e53935; }
                .footer     { margin-top: 20px; font-size: 9px; color: #999; text-align: right; }
            </style>
        </head>
        <body>
            <h1>Academia de Karate-Do SMT — Lista de Asistencia{$tituloFiltro}</h1>
            <h2>Fecha: {$fechaFormato} &nbsp;|&nbsp; Total: <strong>{$asistencias->count()}</strong> alumnos</h2>
            <table>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Nombre Completo</th>
                        <th>N° Control</th>
                        <th>Grupo</th>
                        <th>Especialidad</th>
                        <th>Turno</th>
                        <th>Hora</th>
                        <th>Bachiller</th>
                    </tr>
                </thead>
                <tbody>
                    {$filas}
                </tbody>
            </table>
            <div class='resumen'>
                Bachilleres: <span>{$totalBachilleres}</span> &nbsp;&nbsp;
                Otros alumnos: <span>{$totalOtros}</span>
            </div>
            <div class='footer'>
                Generado el " . now()->format('d/m/Y H:i') . "
            </div>
        </body>
        </html>";
    }
}