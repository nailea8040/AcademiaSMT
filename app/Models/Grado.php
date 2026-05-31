<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grado extends Model
{
    protected $table      = 'grado';
    protected $primaryKey = 'id_grado';
    public    $timestamps = false;

    protected $fillable = [
        'nombreGrado',
        'orden',
    ];

    protected $casts = [
        'id_grado' => 'integer',
        'orden'    => 'integer',
    ];

    // ── Relaciones ────────────────────────────────────────────────

    /**
     * Todos los registros de historial que apuntan a este grado.
     */
    public function historialGrados()
    {
        return $this->hasMany(HistorialGrado::class, 'id_grado', 'id_grado');
    }

    /**
     * Alumnos cuyo grado ACTUAL es este.
     *
     * CORRECCIÓN: el subquery ahora cualifica explícitamente la tabla y
     * la columna (historial_grados.id) para evitar ambigüedad en JOINs.
     */
    public function alumnosActuales()
    {
        return $this->hasMany(HistorialGrado::class, 'id_grado', 'id_grado')
                    ->whereIn('historial_grados.id', function ($sub) {
                        $sub->selectRaw('MAX(hg_inner.id)')
                            ->from('historial_grados as hg_inner')
                            ->groupBy('hg_inner.id_usuario');
                    });
    }

    // ── Scopes ────────────────────────────────────────────────────

    /**
     * Grados ordenados por su campo 'orden' ascendente (cinturón blanco → negro).
     */
    public function scopeOrdenados(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('orden', 'asc');
    }

    /**
     * Alias por si algún blade usa ->porId() sin el campo 'orden'.
     * Ordena por id_grado como fallback.
     */
    public function scopePorId(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('id_grado', 'asc');
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function getNombreAttribute(): string
    {
        return $this->nombreGrado ?? '';
    }
}