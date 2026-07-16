<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fase_responsable', function (Blueprint $table) {
            $table->id('id_responsable');
            $table->enum('fase', ['graficacion', 'mesas', 'premiacion', 'memoria'])->unique();
            $table->integer('id_usuario');
            $table->string('nip_hash', 255);
            $table->tinyInteger('activo')->default(1);
            $table->timestamps();

            $table->foreign('id_usuario')->references('id_usuario')->on('usuario')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fase_responsable');
    }
};
