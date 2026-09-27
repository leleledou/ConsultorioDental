<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];
}
