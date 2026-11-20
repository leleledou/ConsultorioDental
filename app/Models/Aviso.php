<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aviso interno para un odontólogo (CU40, CU41).
 * referencia_id es polimórfico y sin clave foránea: según el tipo apunta a una cita,
 * a un control o a un tratamiento aplicado.
 */
class Aviso extends Model
{
    // Valores literales del enum `tipo` de la tabla.
    public const CITA_PROXIMA = 'cita próxima';
    public const CONTROL_PENDIENTE = 'control pendiente';
    public const SALDO_PENDIENTE = 'saldo pendiente';
    public const ESTUDIO_PENDIENTE = 'estudio diagnóstico pendiente';
    public const SESION_SIN_CITA = 'sesión pendiente sin cita';

    /**
     * Los cinco tipos, en el orden en que se muestran.
     */
    public const TIPOS = [
        self::CITA_PROXIMA,
        self::CONTROL_PENDIENTE,
        self::SALDO_PENDIENTE,
        self::ESTUDIO_PENDIENTE,
        self::SESION_SIN_CITA,
    ];

    /**
     * Nombre de la tabla.
     */
    protected $table = 'avisos';

    /**
     * La tabla no tiene columnas created_at / updated_at.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'odontologo_id',
        'tipo',
        'referencia_id',
        'mensaje',
        'fecha_generacion',
        'leido',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_generacion' => 'datetime',
            'leido' => 'boolean',
        ];
    }

    /**
     * Odontólogo al que va dirigido el aviso.
     */
    public function odontologo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'odontologo_id');
    }

    /**
     * RF-82: cantidad de avisos sin leer del usuario. Una sola consulta liviana sobre
     * ix_avisos_pendiente; la usa el View Composer de la barra de navegación.
     */
    public static function sinLeer(?int $usuarioId): int
    {
        return $usuarioId ? static::where('odontologo_id', $usuarioId)->where('leido', false)->count() : 0;
    }
}
