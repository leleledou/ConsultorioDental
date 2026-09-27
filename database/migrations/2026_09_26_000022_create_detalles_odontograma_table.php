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
        Schema::create('detalles_odontograma', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odontograma_id')
                ->constrained('odontogramas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('pieza_dental_id')
                ->constrained('piezas_dentales')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('estado_pieza_id')
                ->constrained('estados_pieza')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('tratamiento_aplicado_id')
                ->nullable()
                ->constrained('tratamientos_aplicados')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->text('observacion')->nullable();

            $table->unique(['odontograma_id', 'pieza_dental_id'], 'uq_detalles_odontograma');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_odontograma');
    }
};
