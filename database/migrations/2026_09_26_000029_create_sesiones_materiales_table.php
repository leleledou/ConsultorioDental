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
        Schema::create('sesiones_materiales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_id')
                ->constrained('sesiones')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('material_id')
                ->constrained('materiales')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->decimal('cantidad', 8, 2);
            $table->text('observacion')->nullable();

            $table->unique(['sesion_id', 'material_id'], 'uq_sesiones_materiales');
        });

        DB::statement('ALTER TABLE sesiones_materiales ADD CONSTRAINT ck_sesiones_materiales_cantidad CHECK (cantidad > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesiones_materiales');
    }
};
