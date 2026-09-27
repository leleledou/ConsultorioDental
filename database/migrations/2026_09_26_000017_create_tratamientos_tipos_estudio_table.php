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
        Schema::create('tratamientos_tipos_estudio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tratamiento_id')
                ->constrained('tratamientos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('tipo_estudio_id')
                ->constrained('tipos_estudio')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->boolean('obligatorio')->default(false);
            $table->enum('momento_clinico', [
                'previo al tratamiento',
                'durante el tratamiento',
                'control posterior',
            ]);
            $table->text('observacion')->nullable();

            $table->unique(['tratamiento_id', 'tipo_estudio_id'], 'uq_tratamientos_tipos_estudio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tratamientos_tipos_estudio');
    }
};
