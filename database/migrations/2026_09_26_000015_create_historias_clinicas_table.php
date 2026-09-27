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
        Schema::create('historias_clinicas', function (Blueprint $table) {
            $table->id();
            // unique: cada paciente tiene una sola historia clínica.
            $table->foreignId('paciente_id')
                ->unique()
                ->constrained('pacientes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->date('fecha_apertura');
            $table->text('habitos')->nullable();
            $table->text('observaciones_generales')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historias_clinicas');
    }
};
