<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValoracionImplante extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'valoraciones_implante';

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
        'pieza_dental_id',
        'odontologo_id',
        'estudio_diagnostico_id',
        'tratamiento_aplicado_id',
        'fecha_valoracion',
        'estado_hueso',
        'requiere_injerto',
        'observaciones_clinicas',
        'resultado',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_valoracion' => 'date',
            'requiere_injerto' => 'boolean',
        ];
    }

    /**
     * Pieza dental valorada para el implante.
     */
    public function pieza(): BelongsTo
    {
        return $this->belongsTo(PiezaDental::class, 'pieza_dental_id');
    }
}
