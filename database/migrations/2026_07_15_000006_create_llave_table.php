<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llave', function (Blueprint $table) {
            $table->id('id_llave');
            $table->unsignedBigInteger('id_categoria_torneo');
            $table->integer('ronda');
            $table->integer('posicion');
            $table->unsignedBigInteger('id_inscripcion_1')->nullable();
            $table->unsignedBigInteger('id_inscripcion_2')->nullable();
            $table->unsignedBigInteger('ganador_id')->nullable();
            $table->tinyInteger('es_bye')->default(0);
            $table->tinyInteger('es_tercer_lugar')->default(0);
            $table->integer('tatami_asignado')->nullable();
            $table->enum('estado', ['pendiente', 'en_curso', 'completada'])->default('pendiente');
            $table->integer('orden_juego')->nullable();

            $table->foreign('id_categoria_torneo')->references('id_categoria_torneo')->on('categoria_torneo')->onDelete('cascade');
            $table->foreign('id_inscripcion_1')->references('id_inscripcion')->on('inscripcion')->onDelete('set null');
            $table->foreign('id_inscripcion_2')->references('id_inscripcion')->on('inscripcion')->onDelete('set null');
            $table->foreign('ganador_id')->references('id_inscripcion')->on('inscripcion')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llave');
    }
};
