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
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')
                ->constrained('pacientes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('odontologo_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('tratamiento_aplicado_id')
                ->nullable()
                ->constrained('tratamientos_aplicados')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->unsignedSmallInteger('duracion_minutos');
            // Columna generada almacenada: la calcula la base de datos, no se inserta.
            $table->time('hora_fin')
                ->storedAs('ADDTIME(hora_inicio, SEC_TO_TIME(duracion_minutos * 60))');
            $table->string('motivo', 150)->nullable();
            $table->foreignId('estado_cita_id')
                ->constrained('estados_cita')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->text('observaciones')->nullable();
            $table->date('fecha_registro');

            $table->index(['fecha', 'hora_inicio', 'hora_fin'], 'ix_citas_agenda');
        });

        DB::statement('ALTER TABLE citas ADD CONSTRAINT ck_citas_duracion CHECK (duracion_minutos > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
