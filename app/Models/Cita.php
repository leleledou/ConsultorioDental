<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cita extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'citas';

    /**
     * La tabla no tiene columnas created_at / updated_at.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * hora_fin se excluye: es una columna generada por la base de datos.
     *
     * @var list<string>
     */
    protected $fillable = [
        'paciente_id',
        'odontologo_id',
        'tratamiento_aplicado_id',
        'fecha',
        'hora_inicio',
        'duracion_minutos',
        'motivo',
        'estado_cita_id',
        'observaciones',
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
            'fecha' => 'date',
            'fecha_registro' => 'date',
            'duracion_minutos' => 'integer',
        ];
    }

    /**
     * Paciente de la cita.
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    /**
     * Odontólogo responsable (un usuario del sistema).
     */
    public function odontologo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'odontologo_id');
    }

    /**
     * Estado actual de la cita (programada, atendida, cancelada o no asistida).
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoCita::class, 'estado_cita_id');
    }

    /**
     * CU11 y CU12: solo una cita programada se puede reprogramar, cancelar o cambiar de estado.
     */
    public function estaProgramada(): bool
    {
        return $this->estado->nombre === EstadoCita::PROGRAMADA;
    }

    /**
     * Hora de inicio sin segundos (HH:MM), para mostrar.
     */
    public function horaInicioCorta(): string
    {
        return substr($this->hora_inicio, 0, 5);
    }

    /**
     * Hora de fin sin segundos (HH:MM). La calcula la base (RN-09).
     */
    public function horaFinCorta(): string
    {
        return substr((string) $this->hora_fin, 0, 5);
    }

    /**
     * Agrega un texto en una línea nueva de las observaciones, sin borrar lo anterior.
     */
    public function agregarObservacion(string $texto): void
    {
        $this->observaciones = trim(($this->observaciones ?? '')."\n".$texto);
    }
}
