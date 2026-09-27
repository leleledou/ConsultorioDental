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
        Schema::create('tipos_estudio', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_estudio');
    }
};
