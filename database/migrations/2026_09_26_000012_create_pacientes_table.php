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
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('ci', 15)->unique();
            $table->string('nombres', 50);
            $table->string('apellidos', 50);
            $table->date('fecha_nacimiento');
            $table->enum('sexo', ['F', 'M']);
            $table->string('telefono', 15)->nullable();
            $table->string('correo', 100)->nullable();
            $table->string('direccion', 150)->nullable();
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->date('fecha_registro');

            $table->index(['apellidos', 'nombres'], 'ix_pacientes_apellidos');
        });

        DB::statement('ALTER TABLE pacientes ADD CONSTRAINT ck_pacientes_nacimiento CHECK (fecha_nacimiento <= fecha_registro)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
