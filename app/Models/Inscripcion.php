<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    protected $table      = 'inscripcion';
    protected $primaryKey = 'id_inscripcion';
    public    $timestamps = false;

    protected $fillable = [
        'id_torneo',
        'id_categoria_torneo',
        'estado',
        'nombre_completo',
        'fecha_nacimiento',
        'genero',
        'grado_cinta',
        'peso',
        'dojo_procedencia',
        'maestro_cargo',
        'disciplina_inscrita',
    ];

    protected $casts = [
        'id_inscripcion'      => 'integer',
        'id_torneo'           => 'integer',
        'id_categoria_torneo' => 'integer',
        'peso'                => 'decimal:2',
    ];

    public function torneo()
    {
        return $this->belongsTo(Torneo::class, 'id_torneo', 'id_torneo');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaTorneo::class, 'id_categoria_torneo', 'id_categoria_torneo');
    }

    public function llavesLocal()
    {
        return $this->hasMany(Llave::class, 'id_inscripcion_1', 'id_inscripcion');
    }

    public function llavesVisitante()
    {
        return $this->hasMany(Llave::class, 'id_inscripcion_2', 'id_inscripcion');
    }

    public function resultados()
    {
        return $this->hasMany(Resultado::class, 'id_inscripcion', 'id_inscripcion');
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }
}
