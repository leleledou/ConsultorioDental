<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
