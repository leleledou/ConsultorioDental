<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoPieza extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'estados_pieza';

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
        'color_referencia',
        'estado',
    ];

    /**
     * Detalles de odontograma con este estado.
     */
    public function detallesOdontograma(): HasMany
    {
        return $this->hasMany(DetalleOdontograma::class, 'estado_pieza_id');
    }
}
