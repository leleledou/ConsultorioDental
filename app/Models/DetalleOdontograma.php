<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleOdontograma extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'detalles_odontograma';

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
        'odontograma_id',
        'pieza_dental_id',
        'estado_pieza_id',
        'tratamiento_aplicado_id',
        'observacion',
    ];
}
