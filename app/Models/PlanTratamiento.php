<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanTratamiento extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'planes_tratamiento';

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
        'paciente_id',
        'odontologo_id',
        'fecha_creacion',
        'descripcion',
        'costo_total',
        'estado',
    ];

    /**
     * Tratamientos aplicados del plan.
     */
    public function tratamientosAplicados(): HasMany
    {
        return $this->hasMany(TratamientoAplicado::class, 'plan_tratamiento_id');
    }

    /**
     * Paciente del plan.
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    /**
     * Odontólogo responsable (un usuario del sistema).
     */
    public function odontologo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'odontologo_id');
    }
}
