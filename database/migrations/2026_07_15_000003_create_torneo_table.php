<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('torneo', function (Blueprint $table) {
            $table->id('id_torneo');
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->date('fecha');
            $table->time('hora_inicio')->nullable();
            $table->string('ubicacion', 300)->nullable();
            $table->unsignedBigInteger('id_plantilla')->nullable();
            $table->enum('estado', ['borrador', 'inscripcion', 'graficacion', 'mesas', 'premiacion', 'memoria', 'finalizado'])->default('borrador');
            $table->integer('tatami_asignado')->default(1);
            $table->timestamps();

            $table->foreign('id_plantilla')->references('id_plantilla')->on('plantilla_categoria')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('torneo');
    }
};
