<?php

namespace App\Services;

use App\Models\User;

/**
 * Opciones del sistema agrupadas en los 5 paquetes.
 *
 * La usan el menú principal (layouts/navigation) y el tablero (panel), para
 * que los dos muestren siempre las mismas opciones.
 *
 * Cada opción tiene:
 *   - nombre:     texto visible.
 *   - ruta:       nombre de la ruta; null si todavía no está implementada
 *                 (se muestra en gris con la etiqueta "Próximo ciclo").
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
                'descripcion' => 'Especialidades, tratamientos, tarifas, diagnósticos y materiales.',
                'opciones' => [
                    self::opcion('Especialidades', 'especialidades.listar', 'especialidades.*', true),
                    self::opcion('Tratamientos', soloAdmin: true),
                    self::opcion('Tarifas', soloAdmin: true),
                    self::opcion('Diagnósticos', soloAdmin: true),
                    self::opcion('Materiales', soloAdmin: true),
                ],
            ],
            [
                'codigo' => 'P3',
                'nombre' => 'Gestión de Pacientes y Agenda',
                'corto' => 'Pacientes y Agenda',
                'descripcion' => 'Registro y búsqueda de pacientes, citas y agenda del día.',
                'opciones' => [
                    self::opcion('Pacientes', 'pacientes.listar', 'pacientes.*'),
                    self::opcion('Citas'),
                    self::opcion('Agenda'),
                ],
            ],
            [
                'codigo' => 'P4',
                'nombre' => 'Atención Clínica',
                'corto' => 'Atención Clínica',
                'descripcion' => 'Historia clínica, odontograma, planes y sesiones de tratamiento.',
                'opciones' => [
                    self::opcion('Historia clínica y antecedentes'),
                    self::opcion('Odontograma'),
                    self::opcion('Plan de tratamiento'),
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
                    self::opcion('Pagos'),
                    self::opcion('Controles y seguimiento'),
                    self::opcion('Avisos'),
                    self::opcion('Reportes'),
                ],
            ],
        ];
    }

    // Arma una opción del menú. Sin ruta, la opción queda inhabilitada ("Próximo ciclo").
    private static function opcion(string $nombre, ?string $ruta = null, ?string $activa = null, bool $soloAdmin = false): array
    {
        return [
            'nombre' => $nombre,
            'ruta' => $ruta,
            'activa' => $activa,
            'solo_admin' => $soloAdmin,
        ];
    }
}
