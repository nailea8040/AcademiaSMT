<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialGrado extends Model
{
    protected $table      = 'historial_grados';
    protected $primaryKey = 'id';
    public    $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_grado',
        'fecha_obtencion',
        'observaciones',
    ];

    protected $casts = [
        'id'             => 'integer',
        'id_usuario'     => 'integer',
        'id_grado'       => 'integer',
        'fecha_obtencion'=> 'date',
    ];

    // ── Relaciones ────────────────────────────────────────────────

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'id_grado', 'id_grado');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeDelUsuario($query, int $idUsuario)
    {
        return $query->where('id_usuario', $idUsuario)
                     ->orderBy('fecha_obtencion', 'desc');
    }
}