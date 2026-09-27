<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'citas';

    /**
     * La tabla no tiene columnas created_at / updated_at.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * hora_fin se excluye: es una columna generada por la base de datos.
     *
     * @var list<string>
     */
    protected $fillable = [
        'paciente_id',
        'odontologo_id',
        'tratamiento_aplicado_id',
        'fecha',
        'hora_inicio',
        'duracion_minutos',
        'motivo',
        'estado_cita_id',
        'observaciones',
        'fecha_registro',
    ];
}
