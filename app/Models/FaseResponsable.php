<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaseResponsable extends Model
{
    protected $table      = 'fase_responsable';
    protected $primaryKey = 'id_responsable';
    public    $timestamps = true;

    protected $fillable = [
        'fase',
        'id_usuario',
        'nip_hash',
        'activo',
    ];

    protected $casts = [
        'id_responsable' => 'integer',
        'id_usuario'     => 'integer',
        'activo'         => 'integer',
    ];

    protected $hidden = ['nip_hash'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', 1);
    }

    public function scopePorFase($query, string $fase)
    {
        return $query->where('fase', $fase);
    }

    public function validarNip(string $nip): bool
    {
        return \Illuminate\Support\Facades\Hash::check($nip, $this->nip_hash);
    }
}
