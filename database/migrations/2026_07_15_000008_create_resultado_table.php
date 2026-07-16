<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultado', function (Blueprint $table) {
            $table->id('id_resultado');
            $table->unsignedBigInteger('id_categoria_torneo');
            $table->unsignedBigInteger('id_inscripcion');
            $table->enum('puesto', ['1ro', '2do', '3ro']);
            $table->integer('puntos_torneo')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_categoria_torneo')->references('id_categoria_torneo')->on('categoria_torneo')->onDelete('cascade');
            $table->foreign('id_inscripcion')->references('id_inscripcion')->on('inscripcion')->onDelete('cascade');
            $table->unique(['id_categoria_torneo', 'id_inscripcion'], 'unique_inscripcion_categoria');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultado');
    }
};
