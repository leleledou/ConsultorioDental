<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SesionMaterial extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'sesiones_materiales';

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
        'sesion_id',
        'material_id',
        'cantidad',
        'observacion',
    ];

    /**
     * Sesión en la que se usó el material.
     */
    public function sesion(): BelongsTo
    {
        return $this->belongsTo(Sesion::class, 'sesion_id');
    }

    /**
     * Material del catálogo.
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
