<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
     * Historial de tarifas del tratamiento.
     */
    public function tarifas(): HasMany
    {
        return $this->hasMany(Tarifa::class, 'tratamiento_id');
    }

    /**
     * Tarifa en vigor hoy: estado vigente, ya iniciada y sin vencer (fecha_fin_vigencia null o >= hoy).
     * Si hubiera más de una, gana la de inicio más reciente.
     */
    public function tarifaVigente(): HasOne
    {
        return $this->hasOne(Tarifa::class, 'tratamiento_id')
            ->where('estado', 'vigente')
            ->whereDate('fecha_inicio_vigencia', '<=', today())
            ->where(function ($vigencia) {
                $vigencia->whereNull('fecha_fin_vigencia')
                    ->orWhereDate('fecha_fin_vigencia', '>=', today());
            })
            ->orderByDesc('fecha_inicio_vigencia');
    }

    /**
     * Tipos de estudio que pide el tratamiento, con los datos de la tabla intermedia.
     */
    public function tiposEstudio(): BelongsToMany
    {
        return $this->belongsToMany(TipoEstudio::class, 'tratamientos_tipos_estudio', 'tratamiento_id', 'tipo_estudio_id')
            ->withPivot('obligatorio', 'momento_clinico', 'observacion');
    }

    /**
     * Filas de la tabla intermedia tratamientos_tipos_estudio.
     */
    public function tratamientoTiposEstudio(): HasMany
    {
        return $this->hasMany(TratamientoTipoEstudio::class, 'tratamiento_id');
    }

    /**
     * Indica si el tratamiento se puede elegir al programar una cita (CU10).
     */
    public function estaDisponible(): bool
    {
        return $this->estado === 'disponible';
    }
}
