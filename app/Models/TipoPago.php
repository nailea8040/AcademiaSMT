<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPago extends Model
{
    protected $table      = 'tipo_pago';
    protected $primaryKey = 'id_tipo_pago';
    public    $timestamps = false;

    protected $fillable = [
        'nombre_tipo',
    ];

    protected $casts = [
        'id_tipo_pago' => 'integer',
    ];

    // ── Relaciones ────────────────────────────────────────────────

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_tipo_pago', 'id_tipo_pago');
    }
}