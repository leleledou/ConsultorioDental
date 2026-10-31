<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Diagnostico extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'diagnosticos';

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
        'codigo',
        'nombre',
        'descripcion',
        'estado',
    ];

    /**
     * Tratamientos aplicados con este diagnóstico.
     */
    public function tratamientosAplicados(): HasMany
    {
        return $this->hasMany(TratamientoAplicado::class, 'diagnostico_id');
    }
}
