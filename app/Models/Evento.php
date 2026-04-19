<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Evento extends Model
{
    protected $table      = 'evento';
    protected $primaryKey = 'id_evento';
    public    $timestamps = true;

    protected $fillable = [
        'titulo',
        'nombre_evento', // NULL = archivo individual, valor = galería de evento
        'tipo',          // ENUM: 'imagen', 'video'
        'ruta',
        'descripcion',
        'id_usuario',
    ];

    protected $casts = [
        'id_evento'  => 'integer',
        'id_usuario' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Relaciones ────────────────────────────────────────────────

    /** Usuario que subió el archivo */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    // ── Scopes ────────────────────────────────────────────────────

    /** Solo archivos individuales (sin evento agrupador) */
    public function scopeIndividuales($query)
    {
        return $query->whereNull('nombre_evento');
    }

    /** Solo archivos pertenecientes a una galería de evento */
    public function scopeDeEvento($query, string $nombreEvento)
    {
        return $query->where('nombre_evento', $nombreEvento);
    }

    public function scopeImagenes($query)
    {
        return $query->where('tipo', 'imagen');
    }

    public function scopeVideos($query)
    {
        return $query->where('tipo', 'video');
    }

    // ── Helpers ───────────────────────────────────────────────────

    /** URL pública del archivo */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->ruta);
    }

    /** Eliminar el archivo físico del storage */
    public function eliminarArchivo(): bool
    {
        if ($this->ruta && Storage::disk('public')->exists($this->ruta)) {
            return Storage::disk('public')->delete($this->ruta);
        }
        return false;
    }

    public function esImagen(): bool
    {
        return $this->tipo === 'imagen';
    }

    public function esVideo(): bool
    {
        return $this->tipo === 'video';
    }

    public function esIndividual(): bool
    {
        return is_null($this->nombre_evento);
    }
}