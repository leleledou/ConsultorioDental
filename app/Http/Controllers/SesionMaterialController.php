<?php

namespace App\Http\Controllers;

use App\Models\Sesion;
use App\Models\SesionMaterial;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * CU30 Registrar sesión de evolución: materiales usados en la sesión (RF-45, RF-46).
 * Para los dos roles (el administrador también es odontólogo).
 * El registro no tiene estado: quitar un material lo borra de la sesión.
 * Los materiales de una sesión anulada ya no se modifican.
 */
class SesionMaterialController extends Controller
{
    // CREAR: agrega a la sesión un material que todavía no está registrado en ella.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'sesion_id' => ['required', 'integer', 'exists:sesiones,id'],
            'material_id' => ['required', 'integer', Rule::exists('materiales', 'id')->where('estado', 'activo')],
        ] + $this->reglas(), $this->mensajes());

        $error = DB::transaction(function () use ($datos, $request) {
            $sesion = Sesion::lockForUpdate()->findOrFail($datos['sesion_id']);

            if ($sesion->estado === 'anulada') {
                return 'No se puede modificar una sesión anulada.';
            }

            $repetido = SesionMaterial::where('sesion_id', $sesion->id)
                ->where('material_id', $datos['material_id'])
                ->exists();

            if ($repetido) {
                return 'Este material ya está registrado en la sesión. Modifique la cantidad del registro existente.';
            }

            $registro = SesionMaterial::create($datos);

            BitacoraService::registrar(BitacoraService::REGISTRAR_MATERIAL_SESION, $request->user()->id, 'sesiones_materiales', $registro->id);

            return null;
        });

        if ($error) {
            return $this->error($error);
        }

        return redirect()->back()->with('exito', 'Material registrado en la sesión.');
    }

    // MODIFICAR la cantidad y la observación de un material. El id viene en el formulario.
    public function update(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $datos = $request->validate($this->reglas(), $this->mensajes());

        $error = DB::transaction(function () use ($datos, $request) {
            $registro = SesionMaterial::findOrFail($request->input('id'));
            $sesion = Sesion::lockForUpdate()->findOrFail($registro->sesion_id);

            if ($sesion->estado === 'anulada') {
                return 'No se puede modificar una sesión anulada.';
            }

            $registro->update([
                'cantidad' => $datos['cantidad'],
                'observacion' => $datos['observacion'] ?? null,
            ]);

            BitacoraService::registrar(BitacoraService::MODIFICAR_MATERIAL_SESION, $request->user()->id, 'sesiones_materiales', $registro->id);

            return null;
        });

        if ($error) {
            return $this->error($error);
        }

        return redirect()->back()->with('exito', 'Material actualizado.');
    }

    // QUITAR un material de la sesión (borrado físico). El id viene en el formulario.
    public function delete(Request $request): RedirectResponse
    {
        $request->validate($this->reglaId(), $this->mensajes());

        $error = DB::transaction(function () use ($request) {
            $registro = SesionMaterial::findOrFail($request->input('id'));
            $sesion = Sesion::lockForUpdate()->findOrFail($registro->sesion_id);

            if ($sesion->estado === 'anulada') {
                return 'No se puede modificar una sesión anulada.';
            }

            $registro->delete();

            BitacoraService::registrar(BitacoraService::QUITAR_MATERIAL_SESION, $request->user()->id, 'sesiones_materiales', $registro->id);

            return null;
        });

        if ($error) {
            return $this->error($error);
        }

        return redirect()->back()->with('exito', 'Material quitado de la sesión.');
    }

    // Vuelve atrás con un error de negocio (clave "operacion").
    private function error(string $mensaje): RedirectResponse
    {
        return redirect()->back()->withInput()->withErrors(['operacion' => $mensaje]);
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:sesiones_materiales,id']];
    }

    // Reglas de la cantidad y la observación (decimal(8,2)).
    private function reglas(): array
    {
        return [
            'cantidad' => ['bail', 'required', 'numeric', 'gt:0', 'max:999999.99'],
            'observacion' => ['nullable', 'string'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó el material de la sesión.',
            'id.integer' => 'El material de la sesión indicado no es válido.',
            'id.exists' => 'El material de la sesión indicado no existe.',
            'sesion_id.required' => 'No se indicó la sesión.',
            'sesion_id.integer' => 'La sesión indicada no es válida.',
            'sesion_id.exists' => 'La sesión indicada no existe.',
            'material_id.required' => 'Seleccione el material.',
            'material_id.integer' => 'El material indicado no es válido.',
            'material_id.exists' => 'El material indicado no existe o está dado de baja.',
            'cantidad.required' => 'Indique la cantidad del material.',
            'cantidad.numeric' => 'La cantidad debe ser un número.',
            'cantidad.gt' => 'La cantidad debe ser mayor a 0.',
            'cantidad.max' => 'La cantidad no puede superar 999999,99.',
            'observacion.string' => 'La observación debe ser un texto.',
        ];
    }
}
