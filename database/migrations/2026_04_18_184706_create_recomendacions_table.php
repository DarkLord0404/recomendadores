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
        Schema::create('recomendaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->constrained('consultas');
            $table->foreignId('medico_id')->constrained('medicos');
            $table->integer('posicion')->default(1);
            $table->decimal('puntuacion_ia', 5, 2)->nullable();
            $table->text('justificacion_ia')->nullable();
            $table->text('prompt_usado')->nullable();
            $table->string('modelo_ia', 50)->default('gpt-4');
            $table->boolean('seleccionado')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recomendaciones');
    }
};
