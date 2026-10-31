<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TratamientoTipoEstudio extends Model
{
    // Valores de la columna momento_clinico (CU19).
    public const MOMENTOS_CLINICOS = [
        'previo al tratamiento',
        'durante el tratamiento',
        'control posterior',
    ];

    /**
     * Nombre de la tabla.
     */
    protected $table = 'tratamientos_tipos_estudio';

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
        'tratamiento_id',
        'tipo_estudio_id',
        'obligatorio',
        'momento_clinico',
        'observacion',
    ];

    /**
     * Tratamiento que pide el estudio.
     */
    public function tratamiento(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class, 'tratamiento_id');
    }

    /**
     * Tipo de estudio requerido.
     */
    public function tipoEstudio(): BelongsTo
    {
        return $this->belongsTo(TipoEstudio::class, 'tipo_estudio_id');
    }
}
