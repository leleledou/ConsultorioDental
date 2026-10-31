<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Tratamientos aplicados sobre la pieza.
     */
    public function tratamientosAplicados(): HasMany
    {
        return $this->hasMany(TratamientoAplicado::class, 'pieza_dental_id');
    }

    /**
     * Detalles de odontograma de la pieza.
     */
    public function detallesOdontograma(): HasMany
    {
        return $this->hasMany(DetalleOdontograma::class, 'pieza_dental_id');
    }

    /**
     * Estudios diagnósticos de la pieza.
     */
    public function estudiosDiagnosticos(): HasMany
    {
        return $this->hasMany(EstudioDiagnostico::class, 'pieza_dental_id');
    }

    /**
     * Valoraciones de implante de la pieza.
     */
    public function valoracionesImplante(): HasMany
    {
        return $this->hasMany(ValoracionImplante::class, 'pieza_dental_id');
    }
}
