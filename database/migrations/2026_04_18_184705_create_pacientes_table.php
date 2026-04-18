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
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('sexo', ['masculino', 'femenino', 'otro'])->nullable();
            $table->text('alergias')->nullable();
            $table->text('enfermedades_cronicas')->nullable();
            $table->text('medicamentos_actuales')->nullable();
            $table->string('grupo_sanguineo', 10)->nullable();
            $table->text('historial_medico')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
