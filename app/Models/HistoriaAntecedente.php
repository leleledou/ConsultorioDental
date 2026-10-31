<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoriaAntecedente extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'historias_antecedentes';

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
        'historia_clinica_id',
        'antecedente_medico_id',
        'observacion',
        'fecha_registro',
    ];

    /**
     * Historia clínica de la fila.
     */
    public function historiaClinica(): BelongsTo
    {
        return $this->belongsTo(HistoriaClinica::class, 'historia_clinica_id');
    }

    /**
     * Antecedente médico de la fila.
     */
    public function antecedenteMedico(): BelongsTo
    {
        return $this->belongsTo(AntecedenteMedico::class, 'antecedente_medico_id');
    }
}
