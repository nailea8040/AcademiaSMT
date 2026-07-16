<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutorizacionFase extends Model
{
    protected $table      = 'autorizacion_fase';
    protected $primaryKey = 'id_autorizacion';
    public    $timestamps = false;

    protected $fillable = [
        'id_torneo',
        'fase',
        'id_usuario_autoriza',
        'nip_hash',
        'ip_address',
    ];

    protected $casts = [
        'id_autorizacion'     => 'integer',
        'id_torneo'           => 'integer',
        'id_usuario_autoriza' => 'integer',
    ];

    public function torneo()
    {
        return $this->belongsTo(Torneo::class, 'id_torneo', 'id_torneo');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_autoriza', 'id_usuario');
    }

    public function scopePorTorneo($query, int $torneoId)
    {
        return $query->where('id_torneo', $torneoId);
    }

    public function scopePorFase($query, string $fase)
    {
        return $query->where('fase', $fase);
    }
}
