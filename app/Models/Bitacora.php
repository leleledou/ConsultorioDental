<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class Bitacora extends Model
{
    /**
     * Nombre de la tabla.
     */
    protected $table = 'bitacoras';

    /**
     * La tabla no tiene columnas created_at / updated_at (usa fecha_hora).
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'usuario_id',
        'usuario_digitado',
        'accion',
        'tabla_afectada',
        'registro_id',
        'fecha_hora',
        'ip',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    /**
     * RN-02: los registros de la bitácora no pueden modificarse ni eliminarse.
     * Laravel ejecuta booted() una vez al cargar el modelo; aquí se registran
     * los eventos que se disparan antes de actualizar (updating) y de
     * eliminar (deleting) un registro, y en ambos se lanza una excepción.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Los registros de la bitácora no pueden modificarse ni eliminarse.');
        });

        static::deleting(function () {
            throw new LogicException('Los registros de la bitácora no pueden modificarse ni eliminarse.');
        });
    }

    /**
     * Cada registro pertenece a un usuario. Puede ser null en los intentos
     * de login con usuarios inexistentes.
     * Se indica 'usuario_id' porque Laravel adivinaría 'user_id', que no existe.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
