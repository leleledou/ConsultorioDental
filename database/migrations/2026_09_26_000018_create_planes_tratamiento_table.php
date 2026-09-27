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
        Schema::create('planes_tratamiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')
                ->constrained('pacientes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('odontologo_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->date('fecha_creacion');
            $table->text('descripcion')->nullable();
            $table->decimal('costo_total', 10, 2);
            $table->enum('estado', ['activo', 'concluido', 'cancelado'])->default('activo');
        });

        DB::statement('ALTER TABLE planes_tratamiento ADD CONSTRAINT ck_planes_tratamiento_costo CHECK (costo_total >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planes_tratamiento');
    }
};
