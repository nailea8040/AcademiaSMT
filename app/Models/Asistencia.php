<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table      = 'asistencia';
    protected $primaryKey = 'id_asistencia';
    public    $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'fecha',
        'token',
        'registrado_por',
    ];

    protected $casts = [
        'id_asistencia' => 'integer',
        'id_usuario'    => 'integer',
        'registrado_por'=> 'integer',
        'fecha'         => 'datetime',
    ];

    // ── Relaciones ────────────────────────────────────────────────

    /** Alumno que asistió */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    /** Usuario que registró la asistencia (sensei / admin) */
    public function registrador()
    {
        return $this->belongsTo(Usuario::class, 'registrado_por', 'id_usuario');
    }
}