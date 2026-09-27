<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValoracionImplante extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'valoraciones_implante';

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
        'pieza_dental_id',
        'odontologo_id',
        'estudio_diagnostico_id',
        'tratamiento_aplicado_id',
        'fecha_valoracion',
        'estado_hueso',
        'requiere_injerto',
        'observaciones_clinicas',
        'resultado',
    ];
}
