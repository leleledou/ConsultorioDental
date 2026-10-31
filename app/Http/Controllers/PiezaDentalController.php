<?php

namespace App\Http\Controllers;

use App\Models\PiezaDental;
use Illuminate\View\View;

/**
 * CU22 Gestionar piezas dentales y estados de pieza. Solo para el administrador.
 * Las piezas son un catálogo fijo: solo se consultan.
 */
class PiezaDentalController extends Controller
{
    // MOSTRAR todas las piezas dentales por código FDI, sin paginar.
    public function index(): View
    {
        $piezas = PiezaDental::orderBy('codigo_fdi')->get();

        return view('piezas-dentales.index', compact('piezas'));
    }

    // MOSTRAR UNA pieza dental. Su id es el código FDI.
    public function show($id): View
    {
        $pieza = PiezaDental::findOrFail($id);

        return view('piezas-dentales.show', compact('pieza'));
    }
}
