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
        Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('especialidad_id')->constrained('especialidades');
            $table->string('numero_colegiado', 50)->unique();
            $table->string('telefono', 20)->nullable();
            $table->text('bio')->nullable();
            $table->decimal('calificacion_promedio', 3, 2)->default(0);
            $table->integer('total_consultas')->default(0);
            $table->boolean('disponible')->default(true);
            $table->string('foto_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicos');
    }
};
