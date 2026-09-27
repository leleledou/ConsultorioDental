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
        Schema::create('tratamientos_aplicados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_tratamiento_id')
                ->constrained('planes_tratamiento')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('tratamiento_id')
                ->constrained('tratamientos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('odontologo_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('pieza_dental_id')
                ->nullable()
                ->constrained('piezas_dentales')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('diagnostico_id')
                ->constrained('diagnosticos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->text('observacion_diagnostica')->nullable();
            $table->decimal('costo_acordado', 10, 2);
            $table->unsignedSmallInteger('sesiones_planificadas');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->enum('estado', ['planificado', 'en proceso', 'concluido', 'cancelado'])->default('planificado');
            $table->text('observaciones')->nullable();

            $table->index('estado', 'ix_tratamientos_aplicados_estado');
        });

        DB::statement('ALTER TABLE tratamientos_aplicados ADD CONSTRAINT ck_tratamientos_aplicados_costo CHECK (costo_acordado >= 0)');
        DB::statement('ALTER TABLE tratamientos_aplicados ADD CONSTRAINT ck_tratamientos_aplicados_sesiones CHECK (sesiones_planificadas >= 1)');
        DB::statement('ALTER TABLE tratamientos_aplicados ADD CONSTRAINT ck_tratamientos_aplicados_fechas CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tratamientos_aplicados');
    }
};
