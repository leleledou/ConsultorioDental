<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
