<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Alumno
 *
 * Representa el perfil extendido de un usuario con rol 'alumno'.
 * La tabla 'alumno' actúa como una extensión de 'usuario' (relación 1:1).
 *
 * NOTA: El grado actual del alumno NO se gestiona con la columna 'grado'
 * de esta tabla — se obtiene del último registro en 'historial_grados'.
 * La columna 'grado' aquí es legacy y no debe usarse para lógica nueva.
 */
class Alumno extends Model
{
    protected $table      = 'alumno';
    protected $primaryKey = 'id_usuario'; // FK → usuario.id_usuario (no es autoincrement)
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = [
        'id_usuario',
        'fecha_ingreso',
        'estado',
        // 'grado' — columna legacy, no usar para lógica nueva.
        // El grado actual se lee desde historial_grados.
    ];

    protected $casts = [
        'id_usuario'    => 'integer',
        'estado'        => 'integer',
        'fecha_ingreso' => 'date',
    ];

    // ── Relaciones ────────────────────────────────────────────────

    /**
     * Usuario base del alumno (datos personales, correo, teléfono, etc.)
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Historial completo de grados del alumno, del más reciente al más antiguo.
     */
    public function historialGrados()
    {
        return $this->hasMany(HistorialGrado::class, 'id_usuario', 'id_usuario')
                    ->orderBy('fecha_obtencion', 'desc');
    }

    /**
     * Último grado obtenido por el alumno.
     */
    public function gradoActual()
    {
        return $this->hasOne(HistorialGrado::class, 'id_usuario', 'id_usuario')
                    ->with('grado')
                    ->orderBy('fecha_obtencion', 'desc');
    }

    /**
     * Historial de seminarios del alumno.
     */
    public function historialSeminarios()
    {
        return $this->hasMany(HistorialSeminario::class, 'id_usuario', 'id_usuario')
                    ->orderBy('fecha_participacion', 'desc');
    }

    /**
     * Pagos del alumno.
     */
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_usuario', 'id_usuario')
                    ->orderBy('fecha_pago', 'desc');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('estado', 1);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        return $this->usuario?->nombre_completo ?? '';
    }
}