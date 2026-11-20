<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Control posterior de un tratamiento aplicado (CU38, CU39).
 * Sin paciente_id a propósito (3NF): el paciente se deriva por
 * tratamiento_aplicado -> plan_tratamiento -> paciente (ControlService::pacienteDe).
 */
class Control extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'controles';

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
        'tratamiento_aplicado_id',
        'fecha_prevista',
        'motivo',
        'estado',
        'fecha_cumplimiento',
        'observaciones',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_prevista' => 'date',
            'fecha_cumplimiento' => 'date',
        ];
    }

    /**
     * Tratamiento aplicado al que pertenece el control.
     */
    public function tratamientoAplicado(): BelongsTo
    {
        return $this->belongsTo(TratamientoAplicado::class, 'tratamiento_aplicado_id');
    }
}
