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
        Schema::create('tarifas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tratamiento_id')
                ->constrained('tratamientos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->decimal('precio', 10, 2);
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia')->nullable();
            $table->enum('estado', ['vigente', 'cerrada'])->default('vigente');

            $table->unique(['tratamiento_id', 'fecha_inicio_vigencia'], 'uq_tarifas_vigencia');
        });

        DB::statement('ALTER TABLE tarifas ADD CONSTRAINT ck_tarifas_precio CHECK (precio >= 0)');
        DB::statement('ALTER TABLE tarifas ADD CONSTRAINT ck_tarifas_fechas CHECK (fecha_fin_vigencia IS NULL OR fecha_fin_vigencia >= fecha_inicio_vigencia)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarifas');
    }
};
