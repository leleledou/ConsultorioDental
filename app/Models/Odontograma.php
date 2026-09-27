<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Odontograma extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'odontogramas';

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
        'historia_clinica_id',
        'odontologo_id',
        'fecha_registro',
        'tipo',
        'tipo_denticion',
        'observaciones',
        'estado',
    ];
}
