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
    ];

    // ── Relaciones ────────────────────────────────────────────────

    /** Historial de alumnos que han obtenido este grado */
    public function historialGrados()
    {
        return $this->hasMany(HistorialGrado::class, 'id_grado', 'id_grado');
    }

    /** Alumnos que actualmente tienen este grado (último registro en historial) */
    public function alumnosActuales()
    {
        return $this->hasMany(HistorialGrado::class, 'id_grado', 'id_grado')
                    ->whereIn('id', function ($sub) {
                        $sub->selectRaw('MAX(id)')
                            ->from('historial_grados')
                            ->groupBy('id_usuario');
                    });
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeOrdenados($query)
    {
        return $query->orderBy('id_grado', 'asc');
    }
}