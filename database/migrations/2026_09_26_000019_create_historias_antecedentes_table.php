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
        Schema::create('historias_antecedentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('historia_clinica_id')
                ->constrained('historias_clinicas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('antecedente_medico_id')
                ->constrained('antecedentes_medicos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->text('observacion')->nullable();
            $table->date('fecha_registro');

            $table->unique(['historia_clinica_id', 'antecedente_medico_id'], 'uq_historias_antecedentes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historias_antecedentes');
    }
};
