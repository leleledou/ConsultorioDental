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
        Schema::create('sesiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tratamiento_aplicado_id')
                ->constrained('tratamientos_aplicados')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('odontologo_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('cita_id')
                ->nullable()
                ->constrained('citas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->unsignedSmallInteger('numero_sesion');
            $table->date('fecha');
            $table->text('avance_tecnico')->nullable();
            $table->unsignedSmallInteger('duracion_real_minutos');
            $table->text('observaciones')->nullable();
            $table->date('fecha_proxima_sesion')->nullable();
            $table->enum('estado', ['realizada', 'pendiente', 'anulada'])->default('realizada');

            $table->unique(['tratamiento_aplicado_id', 'numero_sesion'], 'uq_sesiones_numero');
        });

        DB::statement('ALTER TABLE sesiones ADD CONSTRAINT ck_sesiones_numero CHECK (numero_sesion >= 1)');
        DB::statement('ALTER TABLE sesiones ADD CONSTRAINT ck_sesiones_duracion CHECK (duracion_real_minutos > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesiones');
    }
};
