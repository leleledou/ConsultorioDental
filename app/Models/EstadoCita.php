<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoCita extends Model
{
    // Nombres del catálogo de estados (EstadoCitaSeeder). Se buscan por nombre, nunca por id.
    public const PROGRAMADA = 'programada';
    public const ATENDIDA = 'atendida';
    public const CANCELADA = 'cancelada';
    public const NO_ASISTIDA = 'no asistida';

    /**
     * Nombre de la tabla.
     */
    protected $table = 'estados_cita';

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
     * Id del estado con ese nombre (una de las constantes de esta clase).
     * firstOrFail() da un error claro si no se cargó EstadoCitaSeeder.
     */
    public static function idDe(string $nombre): int
    {
        return static::where('nombre', $nombre)->firstOrFail()->id;
    }
}
