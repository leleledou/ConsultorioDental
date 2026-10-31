<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AntecedenteMedico extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'antecedentes_medicos';

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
    ];

    /**
     * Historias clínicas que tienen este antecedente.
     */
    public function historias(): BelongsToMany
    {
        return $this->belongsToMany(HistoriaClinica::class, 'historias_antecedentes', 'antecedente_medico_id', 'historia_clinica_id')
            ->withPivot('observacion', 'fecha_registro');
    }
}
