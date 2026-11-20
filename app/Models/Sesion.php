<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sesion extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'sesiones';

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
        'tratamiento_aplicado_id',
        'odontologo_id',
        'cita_id',
        'numero_sesion',
        'fecha',
        'avance_tecnico',
        'duracion_real_minutos',
        'observaciones',
        'fecha_proxima_sesion',
        'estado',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'fecha_proxima_sesion' => 'date',
            'numero_sesion' => 'integer',
            'duracion_real_minutos' => 'integer',
        ];
    }

    /**
     * Tratamiento aplicado al que pertenece la sesión.
     */
    public function tratamientoAplicado(): BelongsTo
    {
        return $this->belongsTo(TratamientoAplicado::class, 'tratamiento_aplicado_id');
    }

    /**
     * Odontólogo que realizó la sesión.
     */
    public function odontologo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'odontologo_id');
    }

    /**
     * Cita de la que se generó la sesión (RF-49). Puede ser null.
     */
    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    /**
     * Materiales usados en la sesión (RF-45).
     */
    public function materiales(): HasMany
    {
        return $this->hasMany(SesionMaterial::class, 'sesion_id');
    }
}
