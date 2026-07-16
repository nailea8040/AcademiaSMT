<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaTorneo extends Model
{
    protected $table      = 'categoria_torneo';
    protected $primaryKey = 'id_categoria_torneo';
    public    $timestamps = false;

    protected $fillable = [
        'id_torneo',
        'id_categoria_def',
        'nombre_categoria',
        'tipo_disciplina',
        'sexo',
        'edad_min',
        'edad_max',
        'peso_min',
        'peso_max',
        'grado_min',
        'grado_max',
        'estado',
        'tatami_asignado',
    ];

    protected $casts = [
        'id_categoria_torneo' => 'integer',
        'id_torneo'           => 'integer',
        'id_categoria_def'    => 'integer',
        'edad_min'            => 'integer',
        'edad_max'            => 'integer',
        'peso_min'            => 'decimal:2',
        'peso_max'            => 'decimal:2',
        'grado_min'           => 'integer',
        'grado_max'           => 'integer',
        'tatami_asignado'     => 'integer',
    ];

    public function torneo()
    {
        return $this->belongsTo(Torneo::class, 'id_torneo', 'id_torneo');
    }

    public function definicion()
    {
        return $this->belongsTo(CategoriaDefinicion::class, 'id_categoria_def', 'id_categoria_def');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'id_categoria_torneo', 'id_categoria_torneo');
    }

    public function llaves()
    {
        return $this->hasMany(Llave::class, 'id_categoria_torneo', 'id_categoria_torneo');
    }

    public function resultados()
    {
        return $this->hasMany(Resultado::class, 'id_categoria_torneo', 'id_categoria_torneo');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeEnCurso($query)
    {
        return $query->where('estado', 'en_curso');
    }

    public function scopeFinalizadas($query)
    {
        return $query->where('estado', 'finalizada');
    }
}
