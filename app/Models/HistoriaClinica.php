<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HistoriaClinica extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'historias_clinicas';

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
        'paciente_id',
        'fecha_apertura',
        'habitos',
        'observaciones_generales',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_apertura' => 'date',
        ];
    }

    /**
     * La historia clínica pertenece a un paciente.
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    /**
     * Antecedentes médicos de la historia, con los datos de la tabla intermedia.
     */
    public function antecedentes(): BelongsToMany
    {
        return $this->belongsToMany(AntecedenteMedico::class, 'historias_antecedentes', 'historia_clinica_id', 'antecedente_medico_id')
            ->withPivot('observacion', 'fecha_registro');
    }
}
