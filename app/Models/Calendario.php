<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendario extends Model
{
    protected $table      = 'calendario';
    protected $primaryKey = 'id_cal';
    public    $timestamps = true; // tiene created_at y updated_at

    protected $fillable = [
        'titulo',
        'fecha',
        'hora',
        'ubicacion',
        'tipo',
        'descripcion',
        'id_usuario',
    ];

    protected $casts = [
        'id_cal'     => 'integer',
        'id_usuario' => 'integer',
        'fecha'      => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Relaciones ────────────────────────────────────────────────

    /** Usuario que creó el evento de calendario */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeProximos($query)
    {
        return $query->where('fecha', '>=', now()->toDateString())
                     ->orderBy('fecha', 'asc')
                     ->orderBy('hora', 'asc');
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}