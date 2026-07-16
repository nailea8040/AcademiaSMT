<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogFase extends Model
{
    protected $table      = 'log_fase';
    protected $primaryKey = 'id_log';
    public    $timestamps = false;

    protected $fillable = [
        'id_torneo',
        'fase_anterior',
        'fase_nueva',
        'id_usuario',
        'id_autorizacion',
    ];

    protected $casts = [
        'id_log'            => 'integer',
        'id_torneo'         => 'integer',
        'id_usuario'        => 'integer',
        'id_autorizacion'   => 'integer',
    ];

    public function torneo()
    {
        return $this->belongsTo(Torneo::class, 'id_torneo', 'id_torneo');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function autorizacion()
    {
        return $this->belongsTo(AutorizacionFase::class, 'id_autorizacion', 'id_autorizacion');
    }

    public function scopePorTorneo($query, int $torneoId)
    {
        return $query->where('id_torneo', $torneoId);
    }
}
