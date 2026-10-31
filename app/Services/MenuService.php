<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Opciones del sistema agrupadas en los 5 paquetes.
 *
 * La usan el menú principal (layouts/navigation) y el tablero (panel), para
 * que los dos muestren siempre las mismas opciones.
 *
 * Cada opción tiene:
 *   - nombre:     texto visible.
 *   - ruta:       nombre de la ruta, sin parámetros; null si todavía no está
 *                 implementada (se muestra en gris con la etiqueta "Próximo ciclo").
 *   - activa:     patrón de rutas que marca la opción como la actual.
 *   - solo_admin: RN-05, solo la ve el administrador.
 */
class MenuService
{
    /**
     * Devuelve los paquetes con las opciones que el usuario puede ver (RN-05).
     * Un paquete sin opciones visibles para el usuario no se devuelve.
     *
     * @return list<array{codigo: string, nombre: string, corto: string, descripcion: string, opciones: list<array{nombre: string, ruta: ?string, activa: ?string, solo_admin: bool}>}>
     */
    public static function paquetes(User $usuario): array
    {
        $esAdministrador = $usuario->rol === 'administrador';
        $visibles = [];

        foreach (self::todos() as $paquete) {
            $paquete['opciones'] = array_values(array_filter(
                $paquete['opciones'],
                fn ($opcion) => $esAdministrador || ! $opcion['solo_admin']
            ));

            if ($paquete['opciones'] !== []) {
                $visibles[] = $paquete;
            }
        }

        return $visibles;
    }

    // Los 5 paquetes con todas sus opciones, sin filtrar por rol.
    private static function todos(): array
    {
        return [
            [
                'codigo' => 'P1',
                'nombre' => 'Administración de Usuarios y Seguridad',
                'corto' => 'Usuarios y Seguridad',
                'descripcion' => 'Cuentas de usuario, roles, acceso y registro de acciones.',
                'opciones' => [
                    self::opcion('Usuarios', 'usuarios.listar', 'usuarios.*', true),
                    self::opcion('Bitácora', 'bitacora.listar', 'bitacora.*', true),
                ],
            ],
            [
                'codigo' => 'P2',
                'nombre' => 'Catálogos Clínicos y de Servicios',
                'corto' => 'Catálogos',
                'descripcion' => 'Especialidades, tratamientos, tarifas, diagnósticos, materiales y demás catálogos.',
                'opciones' => [
                    self::opcion('Especialidades', 'especialidades.listar', 'especialidades.*', true),
                    self::opcion('Tratamientos', 'tratamientos.listar', 'tratamientos.*', true),
                    self::opcion('Tarifas', 'tarifas.listar', 'tarifas.*', true),
                    self::opcion('Diagnósticos', 'diagnosticos.listar', 'diagnosticos.*', true),
                    self::opcion('Tipos de estudio', 'tipos-estudio.listar', 'tipos-estudio.*', true),
                    self::opcion('Materiales', 'materiales.listar', 'materiales.*', true),
                    self::opcion('Antecedentes médicos', 'antecedentes.listar', 'antecedentes.*', true),
                    self::opcion('Piezas y estados', 'piezas-dentales.listar', 'piezas-dentales.*', true),
                    self::opcion('Métodos de pago', 'metodos-pago.listar', 'metodos-pago.*', true),
                ],
            ],
            [
                'codigo' => 'P3',
                'nombre' => 'Gestión de Pacientes y Agenda',
                'corto' => 'Pacientes y Agenda',
                'descripcion' => 'Registro y búsqueda de pacientes, citas y agenda del día.',
                'opciones' => [
                    self::opcion('Pacientes', 'pacientes.listar', 'pacientes.*'),
                    self::opcion('Citas', 'citas.crear', 'citas.*'),
                    self::opcion('Agenda', 'agenda', 'agenda'),
                ],
            ],
            [
                'codigo' => 'P4',
                'nombre' => 'Atención Clínica',
                'corto' => 'Atención Clínica',
                'descripcion' => 'Historia clínica, odontograma, planes y sesiones de tratamiento.',
                'opciones' => [
                    self::opcion('Historia clínica y antecedentes', 'historias.listar', 'historias.*'),
                    self::opcion('Odontograma'),
                    self::opcion('Plan de tratamiento', 'planes.listar', 'planes.*'),
                    self::opcion('Sesiones de tratamiento'),
                    self::opcion('Estudios diagnósticos'),
                ],
            ],
            [
                'codigo' => 'P5',
                'nombre' => 'Pagos, Seguimiento y Reportes',
                'corto' => 'Pagos y Reportes',
                'descripcion' => 'Cobros, controles posteriores, avisos y reportes.',
                'opciones' => [
                    self::opcion('Pagos', 'pagos.crear', 'pagos.*'),
                    self::opcion('Controles y seguimiento'),
                    self::opcion('Avisos'),
                    self::opcion('Reportes', 'reportes.clinicos', 'reportes.*'),
                ],
            ],
        ];
    }

    // Arma una opción del menú. Sin ruta, o si la ruta aún no existe, la opción queda
    // inhabilitada ("Próximo ciclo").
    private static function opcion(string $nombre, ?string $ruta = null, ?string $activa = null, bool $soloAdmin = false): array
    {
        $existe = $ruta !== null && Route::has($ruta);

        return [
            'nombre' => $nombre,
            'ruta' => $existe ? $ruta : null,
            'activa' => $existe ? $activa : null,
            'solo_admin' => $soloAdmin,
        ];
    }
}
