<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PiezaDental extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'piezas_dentales';

    /**
     * La tabla no tiene columnas created_at / updated_at.
     */
    public $timestamps = false;

    /**
     * El id no es autoincremental: es el código FDI de la pieza.
     */
    public $incrementing = false;

    /**
     * Tipo de la clave primaria.
     */
    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'codigo_fdi',
        'nombre',
        'cuadrante',
        'tipo_pieza',
        'tipo_denticion',
    ];
}
