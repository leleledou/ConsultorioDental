<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AntecedenteMedicoController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\DiagnosticoController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\EstadoPiezaController;
use App\Http\Controllers\HistoriaAntecedenteController;
use App\Http\Controllers\HistoriaClinicaController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MetodoPagoController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PiezaDentalController;
use App\Http\Controllers\PlanTratamientoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TarifaController;
use App\Http\Controllers\TipoEstudioController;
use App\Http\Controllers\TratamientoAplicadoController;
use App\Http\Controllers\TratamientoController;
use App\Http\Controllers\TratamientoTipoEstudioController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
 * Rutas de DentalRox - Ciclo 1: Administración de Usuarios y Seguridad, catálogo de
 * especialidades y Gestión de Pacientes. Ciclo 2: catálogo de tratamientos, citas y agenda.
 * Ciclo 3: catálogos clínicos y de servicios, atención clínica, pagos y reportes.
 * Solo se usan Route::get (mostrar) y Route::post (acciones).
 * En update, delete y las demás acciones, el id viaja oculto en el formulario.
 */

// Con sesión, cuenta activa, control de inactividad (RNF-03) y contraseña
// temporal ya reemplazada (CU06, post-condición).
Route::middleware(['auth', 'cuenta.activa', 'inactividad', 'cambio.contrasena'])->group(function () {

    // Entrada: el panel es la pantalla inicial de los dos roles; muestra a cada
    // rol solo sus opciones (RN-05).
    Route::controller(InicioController::class)->group(function () {
        Route::get('/', 'inicio')->name('inicio');
        Route::get('/panel', 'panel')->name('panel');
    });

    // CU13 Consultar agenda (RF-72). La ven los dos roles (el administrador también es odontólogo).
    // Parámetros GET: vista (dia|semana), fecha, odontologo_id y estado_cita_id.
    Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda');

    // CU04 Gestionar usuarios, CU05 Asignar rol y CU06 Restablecer acceso.
    Route::prefix('usuarios')->middleware('rol:administrador')
        ->controller(UsuarioController::class)->group(function () {
            Route::get('/', 'index')->name('usuarios.listar');
            Route::post('/', 'store')->name('usuarios.guardar');
            Route::post('/update', 'update')->name('usuarios.modificar');
            Route::post('/delete', 'delete')->name('usuarios.eliminar'); // inhabilitar
            Route::post('/activar', 'activar')->name('usuarios.habilitar');
            Route::post('/rol', 'asignarRol')->name('usuarios.asignarRol');
            Route::post('/restablecer', 'restablecerAcceso')->name('usuarios.restablecer');
            // Va al final: solo acepta números, para no confundirse con las direcciones fijas.
            Route::get('/{id}', 'show')->whereNumber('id')->name('usuarios.mostrar');
        });

    // CU07 Consultar bitácora (solo lectura).
    Route::prefix('bitacora')->middleware('rol:administrador')
        ->controller(BitacoraController::class)->group(function () {
            Route::get('/', 'index')->name('bitacora.listar');
            Route::get('/{id}', 'show')->whereNumber('id')->name('bitacora.mostrar');
        });

    // Catálogo de especialidades (RF-28).
    Route::prefix('especialidades')->middleware('rol:administrador')
        ->controller(EspecialidadController::class)->group(function () {
            Route::get('/', 'index')->name('especialidades.listar');
            Route::post('/', 'store')->name('especialidades.guardar');
            Route::post('/update', 'update')->name('especialidades.modificar');
            Route::post('/delete', 'delete')->name('especialidades.eliminar');
            Route::get('/{id}', 'show')->whereNumber('id')->name('especialidades.mostrar');
        });

    // CU15 Gestionar catálogo de tratamientos (RF-25).
    Route::prefix('tratamientos')->middleware('rol:administrador')
        ->controller(TratamientoController::class)->group(function () {
            Route::get('/', 'index')->name('tratamientos.listar');
            Route::post('/', 'store')->name('tratamientos.guardar');
            Route::post('/update', 'update')->name('tratamientos.modificar');
            Route::post('/delete', 'delete')->name('tratamientos.eliminar'); // dar de baja
            Route::post('/activar', 'activar')->name('tratamientos.habilitar');
            Route::get('/{id}', 'show')->whereNumber('id')->name('tratamientos.mostrar');
        });

    // CU16 Gestionar tarifas. Una tarifa nunca se modifica ni se elimina.
    Route::prefix('tarifas')->middleware('rol:administrador')
        ->controller(TarifaController::class)->group(function () {
            Route::get('/', 'index')->name('tarifas.listar');
            Route::post('/', 'store')->name('tarifas.guardar');
            // El id es el del tratamiento: muestra su historial.
            Route::get('/{id}', 'show')->whereNumber('id')->name('tarifas.mostrar');
        });

    // CU19 Definir estudios requeridos por tratamiento (RF-31).
    Route::prefix('tratamientos')->middleware('rol:administrador')
        ->controller(TratamientoTipoEstudioController::class)->group(function () {
            Route::post('/estudios', 'store')->name('tratamientos.estudios.guardar');
            Route::post('/estudios/update', 'update')->name('tratamientos.estudios.modificar');
            Route::post('/estudios/delete', 'delete')->name('tratamientos.estudios.quitar');
            Route::get('/{id}/estudios', 'index')->whereNumber('id')->name('tratamientos.estudios');
        });

    // CU17 Gestionar diagnósticos.
    Route::prefix('diagnosticos')->middleware('rol:administrador')
        ->controller(DiagnosticoController::class)->group(function () {
            Route::get('/', 'index')->name('diagnosticos.listar');
            Route::post('/', 'store')->name('diagnosticos.guardar');
            Route::post('/update', 'update')->name('diagnosticos.modificar');
            Route::post('/delete', 'delete')->name('diagnosticos.eliminar'); // dar de baja
            Route::post('/activar', 'activar')->name('diagnosticos.habilitar');
            Route::get('/{id}', 'show')->whereNumber('id')->name('diagnosticos.mostrar');
        });

    // CU18 Gestionar tipos de estudio.
    Route::prefix('tipos-estudio')->middleware('rol:administrador')
        ->controller(TipoEstudioController::class)->group(function () {
            Route::get('/', 'index')->name('tipos-estudio.listar');
            Route::post('/', 'store')->name('tipos-estudio.guardar');
            Route::post('/update', 'update')->name('tipos-estudio.modificar');
            Route::post('/delete', 'delete')->name('tipos-estudio.eliminar'); // dar de baja
            Route::post('/activar', 'activar')->name('tipos-estudio.habilitar');
            Route::get('/{id}', 'show')->whereNumber('id')->name('tipos-estudio.mostrar');
        });

    // CU20 Gestionar materiales.
    Route::prefix('materiales')->middleware('rol:administrador')
        ->controller(MaterialController::class)->group(function () {
            Route::get('/', 'index')->name('materiales.listar');
            Route::post('/', 'store')->name('materiales.guardar');
            Route::post('/update', 'update')->name('materiales.modificar');
            Route::post('/delete', 'delete')->name('materiales.eliminar'); // dar de baja
            Route::post('/activar', 'activar')->name('materiales.habilitar');
            Route::get('/{id}', 'show')->whereNumber('id')->name('materiales.mostrar');
        });

    // CU21 Gestionar antecedentes médicos. Sin estado: delete borra de verdad.
    Route::prefix('antecedentes')->middleware('rol:administrador')
        ->controller(AntecedenteMedicoController::class)->group(function () {
            Route::get('/', 'index')->name('antecedentes.listar');
            Route::post('/', 'store')->name('antecedentes.guardar');
            Route::post('/update', 'update')->name('antecedentes.modificar');
            Route::post('/delete', 'delete')->name('antecedentes.eliminar');
            Route::get('/{id}', 'show')->whereNumber('id')->name('antecedentes.mostrar');
        });

    // CU22 Gestionar piezas dentales y estados de pieza: estados de pieza.
    Route::prefix('estados-pieza')->middleware('rol:administrador')
        ->controller(EstadoPiezaController::class)->group(function () {
            Route::get('/', 'index')->name('estados-pieza.listar');
            Route::post('/', 'store')->name('estados-pieza.guardar');
            Route::post('/update', 'update')->name('estados-pieza.modificar');
            Route::post('/delete', 'delete')->name('estados-pieza.eliminar'); // dar de baja
            Route::post('/activar', 'activar')->name('estados-pieza.habilitar');
            Route::get('/{id}', 'show')->whereNumber('id')->name('estados-pieza.mostrar');
        });

    // CU22 Gestionar piezas dentales y estados de pieza: piezas dentales (solo consulta).
    Route::prefix('piezas-dentales')->middleware('rol:administrador')
        ->controller(PiezaDentalController::class)->group(function () {
            Route::get('/', 'index')->name('piezas-dentales.listar');
            Route::get('/{id}', 'show')->whereNumber('id')->name('piezas-dentales.mostrar');
        });

    // CU37 Gestionar métodos de pago.
    Route::prefix('metodos-pago')->middleware('rol:administrador')
        ->controller(MetodoPagoController::class)->group(function () {
            Route::get('/', 'index')->name('metodos-pago.listar');
            Route::post('/', 'store')->name('metodos-pago.guardar');
            Route::post('/update', 'update')->name('metodos-pago.modificar');
            Route::post('/delete', 'delete')->name('metodos-pago.eliminar'); // dar de baja
            Route::post('/activar', 'activar')->name('metodos-pago.habilitar');
            Route::get('/{id}', 'show')->whereNumber('id')->name('metodos-pago.mostrar');
        });

    // CU08 Gestionar pacientes y CU09 Buscar paciente.
    // Los dos roles: sin middleware rol (el administrador también es odontólogo).
    Route::prefix('pacientes')->controller(PacienteController::class)->group(function () {
        Route::get('/', 'index')->name('pacientes.listar');
        Route::post('/', 'store')->name('pacientes.guardar');
        Route::post('/update', 'update')->name('pacientes.modificar');
        Route::post('/delete', 'delete')->name('pacientes.eliminar'); // inhabilitar
        Route::post('/activar', 'activar')->name('pacientes.habilitar');
        Route::get('/{id}', 'show')->whereNumber('id')->name('pacientes.mostrar');
    });

    // CU10 Programar cita, CU11 Reprogramar o cancelar cita y CU12 Actualizar estado de cita.
    // Los dos roles atienden: sin middleware rol.
    Route::prefix('citas')->controller(CitaController::class)->group(function () {
        Route::get('/', 'index')->name('citas.listar'); // CU12: citas del día
        Route::get('/crear', 'create')->name('citas.crear');
        Route::post('/', 'store')->name('citas.guardar');
        Route::post('/reprogramar', 'reprogramar')->name('citas.reprogramar');
        Route::post('/cancelar', 'cancelar')->name('citas.cancelar');
        Route::post('/estado', 'cambiarEstado')->name('citas.estado');
        Route::get('/{id}', 'show')->whereNumber('id')->name('citas.mostrar');
    });

    // CU23 Gestionar historia clínica. Los dos roles atienden: sin middleware rol.
    // No existe eliminar historia clínica (RF-10).
    Route::prefix('historias')->group(function () {
        Route::controller(HistoriaClinicaController::class)->group(function () {
            Route::post('/update', 'update')->name('historias.modificar');
            Route::get('/{paciente}', 'show')->whereNumber('paciente')->name('historias.mostrar');
        });

        Route::prefix('antecedentes')->controller(HistoriaAntecedenteController::class)->group(function () {
            Route::post('/', 'store')->name('historias.antecedentes.asignar');
            Route::post('/update', 'update')->name('historias.antecedentes.modificar');
            Route::post('/delete', 'delete')->name('historias.antecedentes.quitar');
        });
    });

    // CU27 Gestionar plan de tratamiento. Los dos roles atienden: sin middleware rol.
    Route::prefix('planes')->controller(PlanTratamientoController::class)->group(function () {
        Route::get('/', 'index')->name('planes.listar');
        Route::post('/', 'store')->name('planes.guardar');
        Route::post('/update', 'update')->name('planes.modificar');
        Route::post('/cancelar', 'cancelar')->name('planes.cancelar');
        Route::get('/{id}', 'show')->whereNumber('id')->name('planes.mostrar');
    });

    // CU28 Registrar tratamiento aplicado y CU29 Actualizar estado del tratamiento aplicado.
    // Los dos roles atienden: sin middleware rol.
    Route::controller(TratamientoAplicadoController::class)->group(function () {
        Route::get('/planes/{plan}/tratamientos/crear', 'create')->whereNumber('plan')->name('tratamientos-aplicados.crear');
        Route::post('/tratamientos-aplicados', 'store')->name('tratamientos-aplicados.guardar');
        Route::post('/tratamientos-aplicados/estado', 'cambiarEstado')->name('tratamientos-aplicados.estado');
        Route::get('/tratamientos-aplicados/{id}', 'show')->whereNumber('id')->name('tratamientos-aplicados.mostrar');
    });

    // CU34 Registrar abono, CU35 Anular abono y CU36 Consultar estado de cuenta.
    // Los dos roles atienden: sin middleware rol.
    Route::controller(PagoController::class)->group(function () {
        Route::get('/pacientes/{paciente}/cuenta', 'estadoCuenta')->whereNumber('paciente')->name('pagos.cuenta');
        Route::get('/pagos/crear', 'create')->name('pagos.crear');
        Route::post('/pagos', 'store')->name('pagos.guardar');
        Route::post('/pagos/anular', 'anular')->name('pagos.anular');
    });

    // CU42 a CU44 Generar reportes y CU45 Exportar reporte. Solo leen datos.
    // Los dos roles: sin middleware rol.
    Route::prefix('reportes')->controller(ReporteController::class)->group(function () {
        Route::get('/clinicos', 'clinico')->name('reportes.clinicos');
        Route::get('/agenda', 'agenda')->name('reportes.agenda');
        Route::get('/financieros', 'financiero')->name('reportes.financieros');
        Route::get('/exportar', 'exportar')->name('reportes.exportar');
    });
});

// Rutas de autenticación (login, logout y cambio de contraseña).
require __DIR__.'/auth.php';
