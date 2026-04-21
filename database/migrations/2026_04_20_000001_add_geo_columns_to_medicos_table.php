<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicos', function (Blueprint $table) {
            $table->decimal('latitud', 10, 7)->nullable()->after('foto_url');
            $table->decimal('longitud', 10, 7)->nullable()->after('latitud');
            $table->string('direccion')->nullable()->after('longitud');
        });
    }

    public function down(): void
    {
        Schema::table('medicos', function (Blueprint $table) {
            $table->dropColumn(['latitud', 'longitud', 'direccion']);
        });
    }
};
