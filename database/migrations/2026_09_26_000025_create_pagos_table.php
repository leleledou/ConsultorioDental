<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tratamiento_aplicado_id')
                ->constrained('tratamientos_aplicados')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('metodo_pago_id')
                ->constrained('metodos_pago')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            // Odontólogo que recibe el cobro.
            $table->foreignId('odontologo_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            $table->string('numero_comprobante', 30)->nullable()->unique();
            $table->text('observaciones')->nullable();

            $table->index('fecha_pago', 'ix_pagos_fecha');
        });

        DB::statement('ALTER TABLE pagos ADD CONSTRAINT ck_pagos_monto CHECK (monto > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
