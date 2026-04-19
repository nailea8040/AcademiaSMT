<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RegistroFisico extends Model
{
    protected $table      = 'registro_fisico';
    protected $primaryKey = 'id_registro';
    public    $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'peso',
        'estatura',
        'certificado_medico',
        'fecha_registro',
    ];

    protected $casts = [
        'id_registro'  => 'integer',
        'id_usuario'   => 'integer',
        'peso'         => 'decimal:2',
        'estatura'     => 'decimal:2',
        'fecha_registro'=> 'date',
    ];

    // ── Relaciones ────────────────────────────────────────────────

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    // ── Helpers ───────────────────────────────────────────────────

    /** URL pública del certificado médico */
    public function getUrlCertificadoAttribute(): ?string
    {
        if (!$this->certificado_medico) return null;
        return asset('storage/' . $this->certificado_medico);
    }

    public function tieneCertificado(): bool
    {
        return !is_null($this->certificado_medico) &&
               Storage::disk('public')->exists($this->certificado_medico);
    }

    /** IMC calculado */
    public function getImcAttribute(): ?float
    {
        if (!$this->peso || !$this->estatura || $this->estatura == 0) return null;
        return round($this->peso / ($this->estatura ** 2), 2);
    }
}