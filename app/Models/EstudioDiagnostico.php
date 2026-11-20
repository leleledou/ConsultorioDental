<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstudioDiagnostico extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'estudios_diagnosticos';

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
        'tipo_estudio_id',
        'odontologo_id',
        'pieza_dental_id',
        'tratamiento_aplicado_id',
        'fecha_estudio',
        'centro_radiologico',
        'ruta_archivo',
        'hallazgos',
        'fecha_registro',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_estudio' => 'date',
            'fecha_registro' => 'date',
        ];
    }

    /**
     * Tipo de estudio del catálogo.
     */
    public function tipoEstudio(): BelongsTo
    {
        return $this->belongsTo(TipoEstudio::class, 'tipo_estudio_id');
    }

    /**
     * Pieza dental estudiada. Puede ser null.
     */
    public function pieza(): BelongsTo
    {
        return $this->belongsTo(PiezaDental::class, 'pieza_dental_id');
    }

    /**
     * Paciente del estudio.
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    /**
     * Odontólogo que registró el estudio.
     */
    public function odontologo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'odontologo_id');
    }

    /**
     * Tratamiento aplicado al que corresponde el estudio. Puede ser null.
     */
    public function tratamientoAplicado(): BelongsTo
    {
        return $this->belongsTo(TratamientoAplicado::class, 'tratamiento_aplicado_id');
    }

    /**
     * Valoraciones para implante que este estudio respalda.
     */
    public function valoraciones(): HasMany
    {
        return $this->hasMany(ValoracionImplante::class, 'estudio_diagnostico_id');
    }
}
