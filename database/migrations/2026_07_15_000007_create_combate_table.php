<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combate', function (Blueprint $table) {
            $table->id('id_combate');
            $table->unsignedBigInteger('id_llave');
            $table->unsignedBigInteger('id_inscripcion_rojo');
            $table->unsignedBigInteger('id_inscripcion_azul');
            $table->integer('puntos_rojo')->default(0);
            $table->integer('puntos_azul')->default(0);
            $table->enum('ganador', ['rojo', 'azul']);
            $table->tinyInteger('ippon_rojo')->default(0);
            $table->tinyInteger('ippon_azul')->default(0);
            $table->tinyInteger('wazari_rojo')->default(0);
            $table->tinyInteger('wazari_azul')->default(0);
            $table->integer('yuko_rojo')->default(0);
            $table->integer('yuko_azul')->default(0);
            $table->integer('tiempo_segundos')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_llave')->references('id_llave')->on('llave')->onDelete('cascade');
            $table->foreign('id_inscripcion_rojo')->references('id_inscripcion')->on('inscripcion')->onDelete('cascade');
            $table->foreign('id_inscripcion_azul')->references('id_inscripcion')->on('inscripcion')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combate');
    }
};
