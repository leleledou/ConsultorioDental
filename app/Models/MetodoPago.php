<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodoPago extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'metodos_pago';

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
        'nombre',
        'descripcion',
        'estado',
    ];

    /**
     * Pagos hechos con este método.
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'metodo_pago_id');
    }
}
