<?php

namespace App\Models;

use Database\Factories\EspecialidadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Especialidad extends Model
{
    /** @use HasFactory<EspecialidadFactory> */
    use HasFactory;

    /**
     * Nombre de la tabla (Laravel pluralizaría en inglés como "especialidads").
     */
    protected $table = 'especialidades';

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
     * Una especialidad tiene muchos usuarios (odontólogos).
     * Se indica la columna foránea 'especialidad_id' de la tabla users.
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'especialidad_id');
    }
}
