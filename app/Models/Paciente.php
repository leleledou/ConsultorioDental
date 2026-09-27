<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Paciente extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'pacientes';

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
        'ci',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'sexo',
        'telefono',
        'correo',
        'direccion',
        'observaciones',
        'estado',
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
            'fecha_nacimiento' => 'date',
            'fecha_registro' => 'date',
        ];
    }

    /**
     * Edad en años cumplidos, calculada desde fecha_nacimiento (no se guarda).
     */
    public function edad(): int
    {
        return $this->fecha_nacimiento->age;
    }

    /**
     * Nombre completo para mostrar en listados y fichas.
     */
    public function nombreCompleto(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    /**
     * Cada paciente tiene una sola historia clínica (se crea al registrarlo).
     */
    public function historiaClinica(): HasOne
    {
        return $this->hasOne(HistoriaClinica::class, 'paciente_id');
    }
}
