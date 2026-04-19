<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ocupacion extends Model
{
    protected $table      = 'ocupacion';
    protected $primaryKey = 'id_ocupacion';
    public    $timestamps = false;

    protected $fillable = [
        'nombre_ocupacion',
    ];

    protected $casts = [
        'id_ocupacion' => 'integer',
    ];

    // ── Relaciones ────────────────────────────────────────────────

    /** Tutores que tienen esta ocupación */
    public function tutores()
    {
        return $this->hasMany(Tutor::class, 'id_ocupacion', 'id_ocupacion');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeOrdenadas($query)
    {
        return $query->orderBy('nombre_ocupacion', 'asc');
    }
}