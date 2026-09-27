<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
