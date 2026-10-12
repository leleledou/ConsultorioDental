<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tratamiento extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'tratamientos';

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
        'nombre',
        'descripcion',
        'especialidad_id',
        'duracion_estimada_minutos',
        'numero_sesiones_previstas',
        'requiere_sesiones',
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
            'duracion_estimada_minutos' => 'integer',
            'numero_sesiones_previstas' => 'integer',
            'requiere_sesiones' => 'boolean',
        ];
    }

    /**
     * Cada tratamiento pertenece a una especialidad (CU15).
     */
    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'especialidad_id');
    }

    /**
     * Indica si el tratamiento se puede elegir al programar una cita (CU10).
     */
    public function estaDisponible(): bool
    {
        return $this->estado === 'disponible';
    }
}
