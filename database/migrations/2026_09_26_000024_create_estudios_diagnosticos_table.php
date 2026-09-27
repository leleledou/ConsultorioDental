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
        Schema::create('estudios_diagnosticos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')
                ->constrained('pacientes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('tipo_estudio_id')
                ->constrained('tipos_estudio')
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
            $table->foreignId('tratamiento_aplicado_id')
                ->nullable()
                ->constrained('tratamientos_aplicados')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->date('fecha_estudio');
            $table->string('centro_radiologico', 80)->nullable();
            $table->string('ruta_archivo', 255)->nullable();
            $table->text('hallazgos')->nullable();
            $table->date('fecha_registro');

            $table->index('fecha_estudio', 'ix_estudios_diagnosticos_fecha');
        });

        DB::statement('ALTER TABLE estudios_diagnosticos ADD CONSTRAINT ck_estudios_diagnosticos_fechas CHECK (fecha_registro >= fecha_estudio)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudios_diagnosticos');
    }
};
