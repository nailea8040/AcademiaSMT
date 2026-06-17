<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table      = 'usuario';
    protected $primaryKey = 'id_usuario';
    public    $timestamps = false;

    protected $fillable = [
        'nombre',
        'apaterno',
        'amaterno',
        'fecha_naci',
        'telefono',
        'correo',
        // 'pass' — EXCLUIDO por seguridad. Solo se modifica vía setPassword().
        'rol',
        'estado',
        'fecha_registro',
        'avatar',
        // Columnas bachiller (opcionales — NULL para alumnos no bachiller)
        'numero_control',
        'grupo',
        'especialidad',
        'turno',
        // Columnas nuevas para recuperación de contraseña
        'token_recuperacion',
        'token_expiracion',
        'ultima_solicitud_token',
    ];

    protected $hidden = ['pass', 'token_recuperacion'];

    protected $casts = [
        'estado'                 => 'integer',
        'id_usuario'             => 'integer',
        'fecha_naci'             => 'date',
        'fecha_registro'         => 'datetime',
        'token_expiracion'       => 'datetime',
        'ultima_solicitud_token' => 'datetime',
    ];

    // ── Laravel Auth ──────────────────────────────────────────────────────────

    public function getAuthPassword(): string
    {
        return $this->pass;
    }

    public function getAuthPasswordName(): string
    {
        return 'pass';
    }

    public function getAuthIdentifierName(): string
    {
        return 'id_usuario';
    }

    /**
     * Establece la contraseña del usuario de forma segura.
     * Único método permitido para modificar pass — evita mass assignment.
     */
    public function setPassword(string $password): void
    {
        $this->update(['pass' => \Illuminate\Support\Facades\Hash::make($password)]);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('estado', 1);
    }

    public function scopePorRol($query, string $rol)
    {
        return $query->where('rol', $rol);
    }

    // ── Helpers de rol — BD usa 'admin' (no 'administrador') ─────────────────

    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function esSensei(): bool
    {
        return $this->rol === 'sensei';
    }

    public function esTutor(): bool
    {
        return $this->rol === 'tutor';
    }

    public function esAlumno(): bool
    {
        return $this->rol === 'alumno';
    }

    public function puedeAdministrar(): bool
    {
        return in_array($this->rol, ['admin', 'sensei']);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apaterno} {$this->amaterno}");
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function registroFisico()
    {
        return $this->hasOne(RegistroFisico::class, 'id_usuario', 'id_usuario');
    }

    public function historialGrados()
    {
        return $this->hasMany(HistorialGrado::class, 'id_usuario', 'id_usuario')
                    ->orderBy('fecha_obtencion', 'desc');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_usuario', 'id_usuario')
                    ->orderBy('fecha_pago', 'desc');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'id_usuario', 'id_usuario');
    }

    public function tutor()
    {
        return $this->hasOne(Tutor::class, 'id_Tutor', 'id_usuario');
    }

    public function calendarios()
    {
        return $this->hasMany(Calendario::class, 'id_usuario', 'id_usuario');
    }

    public function eventos()
    {
        return $this->hasMany(Evento::class, 'id_usuario', 'id_usuario');
    }
}