<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'pagos';

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
        'metodo_pago_id',
        'odontologo_id',
        'monto',
        'fecha_pago',
        'numero_comprobante',
        'observaciones',
        // CU35 Anular abono: 'vigente' o 'anulado'; el motivo es obligatorio al anular.
        'estado',
        'motivo_anulacion',
    ];

    /**
     * Método con el que se hizo el pago.
     */
    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    /**
     * Tratamiento aplicado al que se abona el pago.
     */
    public function tratamientoAplicado(): BelongsTo
    {
        return $this->belongsTo(TratamientoAplicado::class, 'tratamiento_aplicado_id');
    }
}
