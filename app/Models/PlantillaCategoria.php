<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantillaCategoria extends Model
{
    protected $table      = 'plantilla_categoria';
    protected $primaryKey = 'id_plantilla';
    public    $timestamps = true;

    protected $fillable = [
        'nombre',
        'descripcion',
        'id_creador',
        'activa',
    ];

    protected $casts = [
        'id_plantilla' => 'integer',
        'id_creador'   => 'integer',
        'activa'       => 'integer',
    ];

    public function creador()
    {
        return $this->belongsTo(Usuario::class, 'id_creador', 'id_usuario');
    }

    public function definiciones()
    {
        return $this->hasMany(CategoriaDefinicion::class, 'id_plantilla', 'id_plantilla');
    }

    public function torneos()
    {
        return $this->hasMany(Torneo::class, 'id_plantilla', 'id_plantilla');
    }

    public function scopeActivas($query)
    {
        return $query->where('activa', 1);
    }
}
