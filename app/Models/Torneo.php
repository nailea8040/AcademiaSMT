<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Torneo extends Model
{
    protected $table      = 'torneo';
    protected $primaryKey = 'id_torneo';
    public    $timestamps = true;

    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha',
        'hora_inicio',
        'ubicacion',
        'id_plantilla',
        'estado',
        'tatami_asignado',
    ];

    protected $casts = [
        'id_torneo'       => 'integer',
        'id_plantilla'    => 'integer',
        'fecha'           => 'date',
        'tatami_asignado' => 'integer',
    ];

    public function plantilla()
    {
        return $this->belongsTo(PlantillaCategoria::class, 'id_plantilla', 'id_plantilla');
    }

    public function categorias()
    {
        return $this->hasMany(CategoriaTorneo::class, 'id_torneo', 'id_torneo');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'id_torneo', 'id_torneo');
    }

    public function puntajesDojo()
    {
        return $this->hasMany(PuntajeDojo::class, 'id_torneo', 'id_torneo');
    }

    public function autorizaciones()
    {
        return $this->hasMany(AutorizacionFase::class, 'id_torneo', 'id_torneo');
    }

    public function logsFase()
    {
        return $this->hasMany(LogFase::class, 'id_torneo', 'id_torneo');
    }

    public function scopeEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }
}
