<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table      = 'pago';
    protected $primaryKey = 'id_pago';
    public    $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_tipo_pago',
        'monto',
        'motivo_pago',
        'fecha_pago',
        'referencia_pago',
        'estado_pago',
    ];

    protected $casts = [
        'id_pago'      => 'integer',
        'id_usuario'   => 'integer',
        'id_tipo_pago' => 'integer',
        'monto'        => 'decimal:2',
        'fecha_pago'   => 'datetime',
    ];

    // ── Relaciones ────────────────────────────────────────────────

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function tipoPago()
    {
        return $this->belongsTo(TipoPago::class, 'id_tipo_pago', 'id_tipo_pago');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeCompletados($query)
    {
        return $query->where('estado_pago', 'Completado');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado_pago', 'Pendiente');
    }

    public function scopeDelUsuario($query, int $idUsuario)
    {
        return $query->where('id_usuario', $idUsuario)
                     ->orderBy('fecha_pago', 'desc');
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function getMontoFormateadoAttribute(): string
    {
        return '$' . number_format($this->monto, 2);
    }

    public function estaCompletado(): bool
    {
        return $this->estado_pago === 'Completado';
    }
}