<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaDefinicion extends Model
{
    protected $table      = 'categoria_definicion';
    protected $primaryKey = 'id_categoria_def';
    public    $timestamps = false;

    protected $fillable = [
        'id_plantilla',
        'nombre_categoria',
        'tipo_disciplina',
        'sexo',
        'edad_min',
        'edad_max',
        'peso_min',
        'peso_max',
        'grado_min',
        'grado_max',
    ];

    protected $casts = [
        'id_categoria_def' => 'integer',
        'id_plantilla'     => 'integer',
        'edad_min'         => 'integer',
        'edad_max'         => 'integer',
        'peso_min'         => 'decimal:2',
        'peso_max'         => 'decimal:2',
        'grado_min'        => 'integer',
        'grado_max'        => 'integer',
    ];

    public function plantilla()
    {
        return $this->belongsTo(PlantillaCategoria::class, 'id_plantilla', 'id_plantilla');
    }

    public function categoriasTorneo()
    {
        return $this->hasMany(CategoriaTorneo::class, 'id_categoria_def', 'id_categoria_def');
    }
}
