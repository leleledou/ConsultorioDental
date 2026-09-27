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
        Schema::create('bitacoras', function (Blueprint $table) {
            $table->id();
            // Nullable: se registran intentos de login con usuarios inexistentes.
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('usuario_digitado', 30)->nullable();
            $table->string('accion', 50);
            $table->string('tabla_afectada', 50)->nullable();
            // Sin llave foránea: puede apuntar a registros de distintas tablas.
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->dateTime('fecha_hora')->index();
            $table->string('ip', 45);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacoras');
    }
};
