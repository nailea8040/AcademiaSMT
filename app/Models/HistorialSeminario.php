<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialSeminario extends Model
{
    // ── CORRECCIÓN: clase estaba vacía ────────────────────────────────────────
    protected $table      = 'historial_seminarios';
    protected $primaryKey = 'id';
    public    $timestamps = false;

    protected $fillable = [
        'id_seminario',
        'id_usuario',
        'fecha_participacion',
        'observaciones',
    ];

    protected $casts = [
        'id'                  => 'integer',
        'id_seminario'        => 'integer',
        'id_usuario'          => 'integer',
        'fecha_participacion' => 'date',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function seminario()
    {
        return $this->belongsTo(Seminario::class, 'id_seminario', 'id_seminario');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}