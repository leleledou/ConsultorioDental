<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odontograma extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'odontogramas';

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
        'odontologo_id',
        'fecha_registro',
        'tipo',
        'tipo_denticion',
        'observaciones',
        'estado',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_registro' => 'date',
        ];
    }

    /**
     * Historia clínica a la que pertenece el odontograma.
     */
    public function historiaClinica(): BelongsTo
    {
        return $this->belongsTo(HistoriaClinica::class, 'historia_clinica_id');
    }

    /**
     * Odontólogo que levantó el odontograma.
     */
    public function odontologo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'odontologo_id');
    }

    /**
     * Piezas registradas en el odontograma, con su estado.
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleOdontograma::class, 'odontograma_id');
    }
}
