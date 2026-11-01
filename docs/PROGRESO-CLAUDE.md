# Progreso del rediseño visual de DentalRox (sesiones con Claude Code)

Resumen para retomar el trabajo en una sesión nueva. Estado al 2026-10-30.

## Reglas de trabajo vigentes

- Rama de trabajo: **`callejas-avance`**. No tocar `main` ni otras ramas.
- **No ejecutar `git commit`, `git push` ni `git merge`**: los gestiona el usuario. No añadir `Co-authored-by`.
- Las pruebas que escriben datos usan **exclusivamente la base `dentalrox_ui_test`** (copia de `dentalrox`). Nunca se escribe en `dentalrox`.
- Alcance: solo presentación (Blade, CSS, Tailwind, Alpine). No se cambian rutas, controladores, servicios, roles, permisos, `MenuService`, reglas de negocio, migraciones ni seeders.
- Se usa la skill `/frontend-visual`, leyendo antes `docs/frontend/DESIGN.md` (fuente de verdad del aspecto). Si una decisión visual cambia, se actualiza ese archivo en la misma tarea.
- Logo oficial único en `docs/branding/`; el PDF de `docs/proyecto/` es la fuente de verdad funcional.

## Fases completadas

| Fase | Alcance | Estado |
|---|---|---|
| Organización | `docs/README.md`, `CLAUDE.md`, `docs/frontend/DESIGN.md`; documentación ordenada en `docs/branding`, `docs/proyecto`, `docs/frontend` | Hecha |
| 1 | Sistema de diseño, layout (sidebar + barra superior + menú móvil), login y panel | Aprobada como referencia definitiva |
| 2 | Módulo Pacientes (listado, búsqueda, paginación, ficha, formularios, validaciones) | Aprobada tras comprobación manual del usuario |
| 3 | Citas y Agenda (programar, ficha, reprogramar, cancelar, cambio de estado, agenda día/semana) | Validación funcional terminada (62/62); el usuario aún debe confirmar el cierre tras sus pruebas en navegador |

## Modificaciones principales

- **Tokens y estilos:** `tailwind.config.js` (paleta `brand`, `ink`, `surface`, `line`, `marca`, sombras `card`/`pop`; `indigo` remapeado al turquesa) y `resources/css/app.css` (`.dr-card`, `.btn*`, `.dr-input`, `.badge`, `.nav-item*`, `.page-header`).
- **Layouts:** `layouts/app`, `navigation`, `topbar` (nuevo), `guest`; vistas `auth/login` y `panel`.
- **Componentes** en `resources/views/components/`: nuevos `icon`, `alerta`, `campo`, `insignia-activo`, `pacientes/acciones`; reestilizados `primary/secondary/danger-button`, `text-input`, `modal`, `estado-cita`, `dropdown*`, `input-error`, `input-label`, `auth-session-status`.
- **Paginación propia:** `resources/views/vendor/pagination/tailwind.blade.php`.
- **Pacientes:** `pacientes/index` y `pacientes/show` (tabla desde `xl`, tarjetas por debajo).
- **Citas y Agenda:** `agenda.blade.php`, `citas/create`, `citas/estado`, `citas/show`. Corrección en la vista día de la agenda: cada cita recibe columna explícita y las franjas "Disponible" sin cita al costado ocupan todo el ancho.
- **Assets:** logo en `public/images/`.
- **Documentación:** `CLAUDE.md`, `docs/README.md`, `docs/frontend/DESIGN.md` (fases 1–3 y pendientes) y este archivo.
- Todo está **sin commitear** en el árbol de trabajo.

## Verificaciones realizadas

- `npm run build` y compilación de vistas (`php artisan view:cache`) correctos.
- Capturas con Chrome headless a 390, 768 y 1440 px (iframes para anchos menores a ~500 px).
- **Pacientes (copia de pruebas):** 56/59 comprobaciones; las 3 restantes eran errores de mi prueba o comportamiento original (el mensaje de error se oculta con `formulario=datos`).
- **Citas y Agenda (copia de pruebas):** 62/62. Cubre programar, reprogramar, cancelar, cambios de estado y estados finales, rechazo de solapes con un único sillón (no importa el odontólogo), mensajes de éxito/validación/error, agenda que refleja las operaciones, y franjas "Disponible" iguales a un cálculo independiente desde la base.
- **Base original `dentalrox`:** huella de las 37 tablas (filas y `CHECKSUM TABLE`) antes y después, **0 diferencias**.

## Estado de las pruebas

- La base **`dentalrox_ui_test`** se conserva (37 tablas, copia exacta de `dentalrox` más datos ficticios).
- Datos ficticios en la copia: un paciente de prueba (id 25), citas ids 155–161 (motivo "PRUEBA-UI"), y filas de bitácora ids 47–59. Las citas, pacientes e historias originales de la copia no se tocaron.
- Hay un volcado temporal de la copia y scripts de verificación (servidor de pruebas con guarda, huellas, pruebas E2E) en el directorio temporal de la sesión, fuera del repositorio. No están versionados.
- El servidor de pruebas del puerto 8130 **no está activo**; hay que levantarlo de nuevo si se necesita.
- No se ha eliminado nada: ni la copia, ni el volcado, ni los temporales.

## Pendientes

1. El usuario debe hacer las **comprobaciones manuales en navegador** de Citas/Agenda: selectores nativos de fecha y hora en móvil; cierre de modales con `Esc` y clic fuera; bloqueo del scroll de fondo; chips de franjas sugeridas; foco y teclado en modales; actualización en pantalla de la "Hora de fin"; gestos táctiles; agenda con muchas citas simultáneas.
2. Confirmación del usuario para cerrar formalmente la fase 3.
3. **Fase 4** (no iniciada): Tratamientos, Especialidades, Usuarios y Bitácora. Solo empezar cuando el usuario lo indique.
4. Decidir con el usuario qué eliminar al terminar (`dentalrox_ui_test`, volcado, archivos temporales, sesiones en `storage/framework/sessions`).
5. Incidencia abierta en `pacientes/index.blade.php`: el mensaje de error se oculta cuando la condición `$errors->any() && ! $volvioFormulario` coincide con `formulario=datos`. Es comportamiento original, sin corregir.
6. Limitación visual conocida: con 3 o más citas simultáneas al costado en la vista día, a 390 px las tarjetas quedan muy angostas y el texto se corta.
7. Pendientes de diseño: favicon y versión del logo con fondo transparente (opcional).

## Precauciones para continuar

- Confirmar la rama con `git branch --show-current` antes de editar.
- Antes de cualquier escritura en base de datos, **verificar la conexión real** a `dentalrox_ui_test` (por ejemplo, con una sonda que devuelva el nombre de la base) y abortar si no coincide.
- Aislar el servidor de pruebas con variables de entorno del proceso (base de pruebas, sesiones en archivo, cookie de sesión propia y `APP_URL` propio). No modificar `.env` ni leer credenciales; no imprimir ni guardar contraseñas.
- Proponer los registros ficticios y pedir autorización expresa antes de cada tanda de escrituras. Un mensaje del usuario que solo repite mi propuesta no cuenta como autorización.
- No modificar ni cancelar datos originales, ni siquiera en la copia. Operar solo sobre registros ficticios identificados.
- No usar `migrate:fresh`, `db:wipe` ni `php artisan test` sobre la base original. No cerrar procesos que no sean los propios (detener servidores por puerto).
- No borrar `dentalrox_ui_test`, el volcado ni los temporales sin aprobación expresa.
- No iniciar una fase nueva sin que el usuario la ordene.
