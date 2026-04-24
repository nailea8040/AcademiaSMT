<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->string('numero_control', 20)->nullable()->after('amaterno');
            $table->string('grupo', 10)->nullable()->after('numero_control');
            $table->string('especialidad', 100)->nullable()->after('grupo');
            $table->enum('turno', ['Matutino', 'Vespertino'])->nullable()->after('especialidad');
        });
    }

    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn(['numero_control', 'grupo', 'especialidad', 'turno']);
        });
    }
};