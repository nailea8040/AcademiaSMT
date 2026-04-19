<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seminario extends Model
{
    protected $table      = 'seminario';
    protected $primaryKey = 'id_seminario';
    public    $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'nombre_seminario',
        'fecha',
        'maestro',
        'descripcion',
        'resultado',
    ];

    protected $casts = [
        'id_seminario' => 'integer',
        'id_usuario'   => 'integer',
        'fecha'        => 'date',
    ];

    // ── Relaciones ────────────────────────────────────────────────

    /** Usuario que organizó / registró el seminario */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    /** Historial de participantes en este seminario */
    public function historialSeminarios()
    {
        return $this->hasMany(HistorialSeminario::class, 'id_seminario', 'id_seminario');
    }

    /** Usuarios que participaron en este seminario (a través del historial) */
    public function participantes()
    {
        return $this->belongsToMany(
            Usuario::class,
            'historial_seminarios',
            'id_seminario',
            'id_usuario'
        )->withPivot('fecha_participacion', 'observaciones');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeRecientes($query)
    {
        return $query->orderBy('fecha', 'desc');
    }

    public function scopeAprobados($query)
    {
        return $query->where('resultado', 'Aprobado');
    }
}