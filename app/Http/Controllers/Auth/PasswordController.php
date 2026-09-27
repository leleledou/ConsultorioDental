<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * CU03 Cambiar contraseña (controlador de Breeze adaptado).
 * Los errores van al $errors normal (no se usa el errorBag 'updatePassword' de Breeze).
 */
class PasswordController extends Controller
{
    // CU03 paso 1: MOSTRAR el formulario de cambio de contraseña.
    public function edit(): View
    {
        return view('contrasena.cambiar');
    }

    // CU03 pasos 3 a 8: validar y GUARDAR la nueva contraseña, y cerrar las demás sesiones.
    public function update(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        // CU03 paso 4: validar. 'bail' detiene las reglas de un campo en el primer error,
        // así cada campo muestra un solo mensaje.
        $request->validate([
            'contrasena_actual' => [
                'bail', 'required', 'string',
                // La contraseña actual debe coincidir con el hash guardado.
                function (string $atributo, mixed $valor, \Closure $fallar) use ($usuario) {
                    if (! Hash::check($valor, $usuario->password)) {
                        $fallar('La contraseña actual es incorrecta.');
                    }
                },
            ],
            'password' => [
                'bail', 'required', 'string',
                'regex:'.User::REGEX_CONTRASENA,
                'confirmed',
                // La nueva contraseña no puede ser igual a la actual.
                function (string $atributo, mixed $valor, \Closure $fallar) use ($usuario) {
                    if (Hash::check($valor, $usuario->password)) {
                        $fallar('La nueva contraseña debe ser distinta de la actual.');
                    }
                },
            ],
        ], [
            'contrasena_actual.required' => 'El campo Contraseña actual es obligatorio.',
            'contrasena_actual.string' => 'La contraseña actual es incorrecta.',
            'password.required' => 'El campo Nueva contraseña es obligatorio.',
            'password.string' => User::MENSAJE_CONTRASENA,
            'password.regex' => User::MENSAJE_CONTRASENA,
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        DB::transaction(function () use ($request, $usuario) {
            // CU03 paso 5: guardar la nueva contraseña cifrada (RN-01) y quitar la obligación de cambiarla.
            $usuario->password = Hash::make($request->input('password'));
            $usuario->debe_cambiar_contrasena = false;
            $usuario->save();

            // CU03 paso 6: registrar en la bitácora (nunca la contraseña).
            BitacoraService::registrar(BitacoraService::CAMBIO_CONTRASENA, $usuario->id, 'users', $usuario->id);

            // CU03 paso 7: cerrar las demás sesiones abiertas del usuario (otros navegadores o equipos).
            DB::table('sessions')
                ->where('user_id', $usuario->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        });

        // Nuevo id para la sesión actual; true elimina la fila del id anterior.
        $request->session()->regenerate(true);

        // CU03 paso 8: confirmar.
        return redirect()->route('inicio')->with('exito', 'Su contraseña fue cambiada correctamente.');
    }
}
