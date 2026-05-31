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
        // Tipo de pago (efectivo, transferencia, MercadoPago, etc.)
        'id_tipo_pago',
        // Concepto de pago (mensualidad, uniforme, examen, etc.)
        'id_concepto',
        // Monto total del pago (lo que se debe pagar en total)
        'monto_total',
        // Monto base / referencia original
        'monto',
        // Monto que ya se ha pagado (puede ser parcial — abonos)
        'monto_pagado',
        // Descripción libre del motivo (cuando no hay concepto predefinido)
        'motivo_pago',
        // Fecha y hora del pago o del último abono
        'fecha_pago',
        // Referencia de la transacción (folio MP, número de transferencia, etc.)
        'referencia_pago',
        // Estado actual: 'Pendiente', 'Completado', 'Suspendido'
        'estado_pago',
        // preference_id de MercadoPago (para rastrear el pago en línea)
        'mp_preference_id',
    ];

    protected $casts = [
        'id_pago'           => 'integer',
        'id_usuario'        => 'integer',
        'id_tipo_pago'      => 'integer',
        'id_concepto'       => 'integer',
        'monto_total'       => 'decimal:2',
        'monto'             => 'decimal:2',
        'monto_pagado'      => 'decimal:2',
        'fecha_pago'        => 'datetime',
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

    public function concepto()
    {
        return $this->belongsTo(ConceptoPago::class, 'id_concepto', 'id_concepto');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeCompletados(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('estado_pago', 'Completado');
    }

    public function scopePendientes(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('estado_pago', 'Pendiente');
    }

    public function scopeSuspendidos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('estado_pago', 'Suspendido');
    }

    public function scopeDelUsuario(\Illuminate\Database\Eloquent\Builder $query, int $idUsuario): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('id_usuario', $idUsuario)
                     ->orderBy('fecha_pago', 'desc');
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Monto pendiente por pagar (diferencia entre total y lo ya pagado).
     */
    public function getMontoPendienteAttribute(): float
    {
        $total  = (float) ($this->monto_total ?? $this->monto ?? 0);
        $pagado = (float) ($this->monto_pagado ?? 0);
        return max(0, $total - $pagado);
    }

    /**
     * Monto total formateado para mostrar en vistas.
     */
    public function getMontoFormateadoAttribute(): string
    {
        $total = $this->monto_total ?? $this->monto ?? 0;
        return '$' . number_format((float) $total, 2);
    }

    /**
     * Monto pagado formateado.
     */
    public function getMontoPagadoFormateadoAttribute(): string
    {
        return '$' . number_format((float) ($this->monto_pagado ?? 0), 2);
    }

    public function estaCompletado(): bool
    {
        return $this->estado_pago === 'Completado';
    }

    public function estaPendiente(): bool
    {
        return $this->estado_pago === 'Pendiente';
    }

    public function estaSuspendido(): bool
    {
        return $this->estado_pago === 'Suspendido';
    }

    /**
     * Indica si el pago fue realizado a través de MercadoPago.
     */
    public function esPagoEnLinea(): bool
    {
        return !is_null($this->mp_preference_id);
    }
}