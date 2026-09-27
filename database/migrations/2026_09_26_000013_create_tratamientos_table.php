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
        Schema::create('tratamientos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->text('descripcion')->nullable();
            $table->foreignId('especialidad_id')
                ->constrained('especialidades')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->unsignedSmallInteger('duracion_estimada_minutos');
            $table->unsignedSmallInteger('numero_sesiones_previstas');
            $table->boolean('requiere_sesiones')->default(false);
            $table->enum('estado', ['disponible', 'no disponible'])->default('disponible');
        });

        DB::statement('ALTER TABLE tratamientos ADD CONSTRAINT ck_tratamientos_duracion CHECK (duracion_estimada_minutos > 0)');
        DB::statement('ALTER TABLE tratamientos ADD CONSTRAINT ck_tratamientos_sesiones CHECK (numero_sesiones_previstas >= 1)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tratamientos');
    }
};
