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
        Schema::create('odontogramas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('historia_clinica_id')
                ->constrained('historias_clinicas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('odontologo_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->date('fecha_registro');
            $table->enum('tipo', ['inicial', 'evolución']);
            $table->enum('tipo_denticion', ['permanente', 'temporal', 'mixta']);
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['activo', 'anulado'])->default('activo');

            $table->unique(['historia_clinica_id', 'fecha_registro'], 'uq_odontogramas_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odontogramas');
    }
};
