<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstudioDiagnostico extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'estudios_diagnosticos';

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
        'tipo_estudio_id',
        'odontologo_id',
        'pieza_dental_id',
        'tratamiento_aplicado_id',
        'fecha_estudio',
        'centro_radiologico',
        'ruta_archivo',
        'hallazgos',
        'fecha_registro',
    ];
}
