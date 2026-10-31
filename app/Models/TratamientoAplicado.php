<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TratamientoAplicado extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'tratamientos_aplicados';

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
        'plan_tratamiento_id',
        'tratamiento_id',
        'odontologo_id',
        'pieza_dental_id',
        'diagnostico_id',
        'observacion_diagnostica',
        'costo_acordado',
        'sesiones_planificadas',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'observaciones',
    ];

    /**
     * Tratamiento del catálogo que se aplica.
     */
    public function tratamiento(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class, 'tratamiento_id');
    }

    /**
     * Diagnóstico que justifica el tratamiento.
     */
    public function diagnostico(): BelongsTo
    {
        return $this->belongsTo(Diagnostico::class, 'diagnostico_id');
    }

    /**
     * Pieza dental tratada. Puede ser null si no es sobre una pieza concreta.
     */
    public function piezaDental(): BelongsTo
    {
        return $this->belongsTo(PiezaDental::class, 'pieza_dental_id');
    }

    /**
     * Plan de tratamiento al que pertenece.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanTratamiento::class, 'plan_tratamiento_id');
    }

    /**
     * Sesiones del tratamiento.
     */
    public function sesiones(): HasMany
    {
        return $this->hasMany(Sesion::class, 'tratamiento_aplicado_id');
    }

    /**
     * Todos los pagos, incluidos los anulados.
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'tratamiento_aplicado_id');
    }

    /**
     * Solo los pagos que cuentan para el saldo (CU35: los anulados no cuentan).
     */
    public function pagosVigentes(): HasMany
    {
        return $this->pagos()->where('estado', 'vigente');
    }
}
