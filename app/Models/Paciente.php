<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'pacientes';

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
        'ci',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'sexo',
        'telefono',
        'correo',
        'direccion',
        'observaciones',
        'estado',
        'fecha_registro',
    ];
}
