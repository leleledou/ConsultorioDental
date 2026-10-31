<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tarifa extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'tarifas';

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
        'precio',
        'fecha_inicio_vigencia',
        'fecha_fin_vigencia',
        'estado',
    ];

    /**
     * Tratamiento al que pertenece la tarifa.
     */
    public function tratamiento(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class, 'tratamiento_id');
    }
}
