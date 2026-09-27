<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aviso extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'avisos';

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
        'odontologo_id',
        'tipo',
        'referencia_id',
        'mensaje',
        'fecha_generacion',
        'leido',
    ];
}
