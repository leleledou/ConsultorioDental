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
        // Sin paciente_id a propósito (normalización): el paciente se obtiene por
        // tratamiento_aplicado -> plan_tratamiento -> paciente.
        Schema::create('controles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tratamiento_aplicado_id')
                ->constrained('tratamientos_aplicados')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->date('fecha_prevista');
            $table->string('motivo', 150)->nullable();
            $table->enum('estado', ['pendiente', 'cumplido', 'vencido'])->default('pendiente');
            $table->date('fecha_cumplimiento')->nullable();
            $table->text('observaciones')->nullable();

            $table->index(['estado', 'fecha_prevista'], 'ix_controles_pendiente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controles');
    }
};
