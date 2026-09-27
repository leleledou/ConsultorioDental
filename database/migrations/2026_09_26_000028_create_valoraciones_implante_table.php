<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('valoraciones_implante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')
                ->constrained('pacientes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('pieza_dental_id')
                ->constrained('piezas_dentales')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('odontologo_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('estudio_diagnostico_id')
                ->nullable()
                ->constrained('estudios_diagnosticos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('tratamiento_aplicado_id')
                ->nullable()
                ->constrained('tratamientos_aplicados')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->date('fecha_valoracion');
            $table->enum('estado_hueso', ['suficiente', 'regular', 'insuficiente']);
            $table->boolean('requiere_injerto')->default(false);
            $table->text('observaciones_clinicas')->nullable();
            $table->enum('resultado', ['apto', 'apto con condiciones', 'no apto']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valoraciones_implante');
    }
};
