<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoEstudio extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'tipos_estudio';

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
     * Tratamientos que piden este tipo de estudio.
     */
    public function tratamientos(): BelongsToMany
    {
        return $this->belongsToMany(Tratamiento::class, 'tratamientos_tipos_estudio', 'tipo_estudio_id', 'tratamiento_id')
            ->withPivot('obligatorio', 'momento_clinico', 'observacion');
    }

    /**
     * Filas de la tabla intermedia tratamientos_tipos_estudio.
     */
    public function tratamientoTiposEstudio(): HasMany
    {
        return $this->hasMany(TratamientoTipoEstudio::class, 'tipo_estudio_id');
    }

    /**
     * Estudios realizados de este tipo.
     */
    public function estudiosDiagnosticos(): HasMany
    {
        return $this->hasMany(EstudioDiagnostico::class, 'tipo_estudio_id');
    }
}
