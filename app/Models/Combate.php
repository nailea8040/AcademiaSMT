<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Combate extends Model
{
    protected $table      = 'combate';
    protected $primaryKey = 'id_combate';
    public    $timestamps = false;

    protected $fillable = [
        'id_llave',
        'id_inscripcion_rojo',
        'id_inscripcion_azul',
        'puntos_rojo',
        'puntos_azul',
        'ganador',
        'ippon_rojo',
        'ippon_azul',
        'wazari_rojo',
        'wazari_azul',
        'yuko_rojo',
        'yuko_azul',
        'tiempo_segundos',
        'observaciones',
    ];

    protected $casts = [
        'id_combate'         => 'integer',
        'id_llave'           => 'integer',
        'id_inscripcion_rojo' => 'integer',
        'id_inscripcion_azul' => 'integer',
        'puntos_rojo'        => 'integer',
        'puntos_azul'        => 'integer',
        'ippon_rojo'         => 'integer',
        'ippon_azul'         => 'integer',
        'wazari_rojo'        => 'integer',
        'wazari_azul'        => 'integer',
        'yuko_rojo'          => 'integer',
        'yuko_azul'          => 'integer',
        'tiempo_segundos'    => 'integer',
    ];

    public function llave()
    {
        return $this->belongsTo(Llave::class, 'id_llave', 'id_llave');
    }

    public function competidorRojo()
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion_rojo', 'id_inscripcion');
    }

    public function competidorAzul()
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion_azul', 'id_inscripcion');
    }

    public function scopePorLlave($query, int $llaveId)
    {
        return $query->where('id_llave', $llaveId);
    }
}
