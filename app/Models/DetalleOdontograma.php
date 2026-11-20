<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * Odontograma al que pertenece el detalle.
     */
    public function odontograma(): BelongsTo
    {
        return $this->belongsTo(Odontograma::class, 'odontograma_id');
    }

    /**
     * Pieza dental registrada.
     */
    public function pieza(): BelongsTo
    {
        return $this->belongsTo(PiezaDental::class, 'pieza_dental_id');
    }

    /**
     * Estado de la pieza en este odontograma.
     */
    public function estadoPieza(): BelongsTo
    {
        return $this->belongsTo(EstadoPieza::class, 'estado_pieza_id');
    }

    /**
     * Tratamiento aplicado vinculado (opcional, RF-20).
     */
    public function tratamientoAplicado(): BelongsTo
    {
        return $this->belongsTo(TratamientoAplicado::class, 'tratamiento_aplicado_id');
    }
}
