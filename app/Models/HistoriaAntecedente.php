<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoriaAntecedente extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'historias_antecedentes';

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
        'antecedente_medico_id',
        'observacion',
        'fecha_registro',
    ];
}
