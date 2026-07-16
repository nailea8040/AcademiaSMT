<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resultado extends Model
{
    protected $table      = 'resultado';
    protected $primaryKey = 'id_resultado';
    public    $timestamps = false;

    protected $fillable = [
        'id_categoria_torneo',
        'id_inscripcion',
        'puesto',
        'puntos_torneo',
    ];

    protected $casts = [
        'id_resultado'        => 'integer',
        'id_categoria_torneo' => 'integer',
        'id_inscripcion'      => 'integer',
        'puntos_torneo'       => 'integer',
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaTorneo::class, 'id_categoria_torneo', 'id_categoria_torneo');
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion', 'id_inscripcion');
    }

    public function scopePorCategoria($query, int $categoriaId)
    {
        return $query->where('id_categoria_torneo', $categoriaId);
    }

    public function scopePorPuesto($query, string $puesto)
    {
        return $query->where('puesto', $puesto);
    }

    public function scopeOro($query)
    {
        return $query->where('puesto', '1ro');
    }

    public function scopePlata($query)
    {
        return $query->where('puesto', '2do');
    }

    public function scopeBronce($query)
    {
        return $query->where('puesto', '3ro');
    }
}
