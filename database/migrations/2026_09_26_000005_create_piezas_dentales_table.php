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
        Schema::create('piezas_dentales', function (Blueprint $table) {
            // No autoincremental: el id es el código FDI. BIGINT UNSIGNED para
            // que sea compatible con las foreignId() que lo referencian.
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedTinyInteger('codigo_fdi')->unique();
            $table->string('nombre', 30);
            $table->unsignedTinyInteger('cuadrante');
            $table->enum('tipo_pieza', ['incisivo', 'canino', 'premolar', 'molar']);
            $table->enum('tipo_denticion', ['permanente', 'temporal']);
        });

        DB::statement('ALTER TABLE piezas_dentales ADD CONSTRAINT ck_piezas_dentales_id CHECK (id = codigo_fdi)');
        DB::statement('ALTER TABLE piezas_dentales ADD CONSTRAINT ck_piezas_dentales_cuadrante CHECK (cuadrante BETWEEN 1 AND 8)');
        DB::statement('ALTER TABLE piezas_dentales ADD CONSTRAINT ck_piezas_dentales_fdi CHECK (codigo_fdi BETWEEN 11 AND 85)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('piezas_dentales');
    }
};
