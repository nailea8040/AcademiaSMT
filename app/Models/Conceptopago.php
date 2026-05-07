<?php
// app/Models/ConceptoPago.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConceptoPago extends Model
{
    protected $table      = 'concepto_pago';
    protected $primaryKey = 'id_concepto';
    public    $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'monto_sugerido',
        'activo',
    ];

    protected $casts = [
        'id_concepto'    => 'integer',
        'monto_sugerido' => 'decimal:2',
        'activo'         => 'boolean',
    ];

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_concepto', 'id_concepto');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', 1)->orderBy('nombre');
    }
}