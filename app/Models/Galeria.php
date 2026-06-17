<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Tabla: galeria
 * PK:    id_gal  (AUTO_INCREMENT)
 *
 * Columnas:
 *   id_gal, titulo, tipo ENUM('imagen','video'),
 *   ruta, descripcion, id_usuario (FK→usuario),
 *   created_at, updated_at
 */
class Galeria extends Model
{
    protected $table      = 'galeria';
    protected $primaryKey = 'id_gal';
    public    $timestamps = true;           // BD tiene created_at y updated_at

    protected $fillable = [
        'titulo', 'tipo', 'ruta', 'descripcion', 'id_usuario',
    ];

    // ── Accessors ───────────────────────────────────────────────

    /** URL pública del archivo almacenado en storage/public */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->ruta);
    }

    /** Verdadero si el archivo es una imagen */
    public function getEsImagenAttribute(): bool
    {
        return $this->tipo === 'imagen';
    }

    // ── Relaciones ──────────────────────────────────────────────

    /** Usuario que subió el archivo */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}