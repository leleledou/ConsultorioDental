<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * CU35 Anular abono: un pago no se borra, se marca como anulado con su motivo.
 * Migración nueva porque create_pagos_table ya se ejecutó en Railway.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->enum('estado', ['vigente', 'anulado'])->default('vigente')->after('observaciones');
            $table->string('motivo_anulacion', 150)->nullable()->after('estado');
        });

        // Un pago anulado siempre registra el motivo de la anulación.
        DB::statement("ALTER TABLE pagos ADD CONSTRAINT ck_pagos_anulacion CHECK (estado <> 'anulado' OR motivo_anulacion IS NOT NULL)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Primero se quita el CHECK: depende de las dos columnas.
        DB::statement('ALTER TABLE pagos DROP CONSTRAINT ck_pagos_anulacion');

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn(['estado', 'motivo_anulacion']);
        });
    }
};
