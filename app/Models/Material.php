<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'materiales';

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
        'categoria',
        'unidad_medida',
        'estado',
    ];

    /**
     * Usos del material en las sesiones.
     */
    public function sesionesMateriales(): HasMany
    {
        return $this->hasMany(SesionMaterial::class, 'material_id');
    }
}
