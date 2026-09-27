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
        Schema::create('avisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odontologo_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->enum('tipo', [
                'cita próxima',
                'control pendiente',
                'saldo pendiente',
                'estudio diagnóstico pendiente',
                'sesión pendiente sin cita',
            ]);
            // Sin llave foránea: apunta a distintas tablas según el tipo.
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->text('mensaje');
            $table->dateTime('fecha_generacion');
            $table->boolean('leido')->default(false);

            $table->index(['odontologo_id', 'leido'], 'ix_avisos_pendiente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avisos');
    }
};
