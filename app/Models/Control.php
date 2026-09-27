<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Control extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'controles';

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
        'fecha_prevista',
        'motivo',
        'estado',
        'fecha_cumplimiento',
        'observaciones',
    ];
}
