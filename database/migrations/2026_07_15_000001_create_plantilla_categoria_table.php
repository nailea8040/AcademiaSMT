<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantilla_categoria', function (Blueprint $table) {
            $table->id('id_plantilla');
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->integer('id_creador');
            $table->tinyInteger('activa')->default(1);
            $table->timestamps();

            $table->foreign('id_creador')->references('id_usuario')->on('usuario')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantilla_categoria');
    }
};
