<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
