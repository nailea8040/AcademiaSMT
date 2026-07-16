<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Llave extends Model
{
    protected $table      = 'llave';
    protected $primaryKey = 'id_llave';
    public    $timestamps = false;

    protected $fillable = [
        'id_categoria_torneo',
        'ronda',
        'posicion',
        'id_inscripcion_1',
        'id_inscripcion_2',
        'ganador_id',
        'es_bye',
        'es_tercer_lugar',
        'tatami_asignado',
        'estado',
        'orden_juego',
    ];

    protected $casts = [
        'id_llave'             => 'integer',
        'id_categoria_torneo'  => 'integer',
        'ronda'                => 'integer',
        'posicion'             => 'integer',
        'id_inscripcion_1'     => 'integer',
        'id_inscripcion_2'     => 'integer',
        'ganador_id'           => 'integer',
        'es_bye'               => 'integer',
        'es_tercer_lugar'      => 'integer',
        'tatami_asignado'      => 'integer',
        'orden_juego'          => 'integer',
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaTorneo::class, 'id_categoria_torneo', 'id_categoria_torneo');
    }

    public function competidor1()
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion_1', 'id_inscripcion');
    }

    public function competidor2()
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion_2', 'id_inscripcion');
    }

    public function ganador()
    {
        return $this->belongsTo(Inscripcion::class, 'ganador_id', 'id_inscripcion');
    }

    public function combate()
    {
        return $this->hasOne(Combate::class, 'id_llave', 'id_llave');
    }

    public function scopePorCategoria($query, int $categoriaId)
    {
        return $query->where('id_categoria_torneo', $categoriaId);
    }

    public function scopePorRonda($query, int $ronda)
    {
        return $query->where('ronda', $ronda);
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeTercerLugar($query)
    {
        return $query->where('es_tercer_lugar', 1);
    }
}
