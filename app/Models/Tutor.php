<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tutor extends Model
{
    protected $table    = 'tutor';
    public $timestamps  = false;
    public $incrementing = false; // no es auto-increment, es FK → usuario.id_usuario

    // ── CORRECCIÓN CRÍTICA ────────────────────────────────────────────────────
    // La columna en BD se llama 'id_Tutor' (T mayúscula).
    // Eloquent usa la PK para find(), save(), delete(), etc.
    // Si la BD usa 'id_Tutor', esto debe coincidir exactamente.
    // Verifica con: DESCRIBE tutor;
    // Si la columna es 'id_Tutor' → dejar así.
    // Si la columna es 'id_tutor' → cambiar a 'id_tutor'.
    protected $primaryKey = 'id_Tutor';

    protected $fillable = [
        'id_Tutor',           // FK → usuario.id_usuario
        'id_ocupacion',
        'relacion_estudiante',
    ];

    protected $casts = [
        'id_Tutor'     => 'integer',
        'id_ocupacion' => 'integer',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    /** Usuario base del tutor (relación 1:1 extendida) */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_Tutor', 'id_usuario');
    }

    /** Ocupación del tutor */
    public function ocupacion()
    {
        return $this->belongsTo(Ocupacion::class, 'id_ocupacion', 'id_ocupacion');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->whereHas('usuario', fn($q) => $q->where('estado', 1));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        return $this->usuario?->nombre_completo ?? '';
    }
}