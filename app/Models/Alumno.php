<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alumno extends Model
{
    // Tabla asociada
    protected $table = 'alumno';

    // Campos editables
    protected $fillable = [
        'id_usuario',
        'grado',
        'fecha_ingreso',
        'estado'
    ];

    // Relación con usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}