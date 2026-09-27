<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // RN-01: mínimo 8 caracteres, con minúsculas, mayúsculas, números y caracteres especiales, sin espacios
    public const REGEX_CONTRASENA = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d\s])\S{8,}$/';

    // Mensaje que se muestra cuando una contraseña no cumple RN-01
    public const MENSAJE_CONTRASENA = 'La contraseña debe tener al menos 8 caracteres, con mayúsculas, minúsculas, números y caracteres especiales, y sin espacios.';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombres',
        'apellidos',
        'ci',
        'matricula_profesional',
        'especialidad_id',
        'correo',
        'usuario',
        'password',
        'telefono',
        'rol',
        'estado',
        'intentos_fallidos',
        'bloqueado_hasta',
        'debe_cambiar_contrasena',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'bloqueado_hasta' => 'datetime',
            'debe_cambiar_contrasena' => 'boolean',
            'intentos_fallidos' => 'integer',
        ];
    }

    /**
     * RN-03: indica si la cuenta está bloqueada en este momento
     * (tiene una hora de fin de bloqueo que todavía no llegó).
     */
    public function estaBloqueado(): bool
    {
        return $this->bloqueado_hasta !== null && $this->bloqueado_hasta->isFuture();
    }

    /**
     * RN-03: minutos que faltan para que termine el bloqueo, redondeados
     * hacia arriba y como mínimo 1. Devuelve 0 si la cuenta no está bloqueada.
     */
    public function minutosBloqueoRestantes(): int
    {
        if (! $this->estaBloqueado()) {
            return 0;
        }

        $segundos = now()->diffInSeconds($this->bloqueado_hasta, true);

        return max(1, (int) ceil($segundos / 60));
    }

    /**
     * Cada usuario pertenece a una especialidad.
     * Se indica la columna foránea 'especialidad_id' de esta tabla.
     */
    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'especialidad_id');
    }

    /**
     * Un usuario tiene muchos registros de bitácora.
     * Se indica 'usuario_id' porque Laravel adivinaría 'user_id', que no existe.
     */
    public function bitacoras(): HasMany
    {
        return $this->hasMany(Bitacora::class, 'usuario_id');
    }
}
