<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Tareas programadas del Laravel Scheduler. Actor: Temporizador (el cron ejecuta
 * `php artisan schedule:run` cada minuto).
 * CU39 Actualizar controles vencidos (RF-80) y CU40 Generar avisos internos (RF-81).
 * Los avisos corren después, para que los controles ya figuren como vencidos.
 * Las dos tienen además un disparo manual en la interfaz (flujo alternativo).
 */
Schedule::command('controles:vencer')->dailyAt('00:05')->timezone('America/La_Paz');
Schedule::command('avisos:generar')->dailyAt('00:15')->timezone('America/La_Paz');
