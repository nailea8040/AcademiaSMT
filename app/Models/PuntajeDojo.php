<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PuntajeDojo extends Model
{
    protected $table      = 'puntaje_dojo';
    protected $primaryKey = 'id_puntaje';
    public    $timestamps = false;

    protected $fillable = [
        'id_torneo',
        'dojo_nombre',
        'puntos_1ro',
        'puntos_2do',
        'puntos_3ro',
    ];

    protected $casts = [
        'id_puntaje'   => 'integer',
        'id_torneo'    => 'integer',
        'puntos_1ro'   => 'integer',
        'puntos_2do'   => 'integer',
        'puntos_3ro'   => 'integer',
        'total_puntos' => 'integer',
    ];

    protected $guarded = ['total_puntos'];

    public function torneo()
    {
        return $this->belongsTo(Torneo::class, 'id_torneo', 'id_torneo');
    }

    public function scopePorTorneo($query, int $torneoId)
    {
        return $query->where('id_torneo', $torneoId);
    }

    public function scopeRanking($query)
    {
        return $query->orderBy('total_puntos', 'desc');
    }
}
