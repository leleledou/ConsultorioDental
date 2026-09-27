<?php

/*
 * Parámetros de seguridad de DentalRox.
 * Se leen en el código con config('dentalrox.<clave>').
 */

return [
    // RN-03: intentos fallidos permitidos antes del bloqueo
    'intentos_maximos' => 3,
    // RN-03: minutos de bloqueo temporal (parámetro configurable)
    'minutos_bloqueo' => 15,
    // RNF-03: minutos de inactividad antes de cerrar la sesión
    'minutos_inactividad' => env('DENTALROX_MINUTOS_INACTIVIDAD', 30),
];
