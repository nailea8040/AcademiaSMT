<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categoria_torneo', function (Blueprint $table) {
            $table->id('id_categoria_torneo');
            $table->unsignedBigInteger('id_torneo');
            $table->unsignedBigInteger('id_categoria_def')->nullable();
            $table->string('nombre_categoria', 150);
            $table->enum('tipo_disciplina', ['kata', 'kumite', 'ambas']);
            $table->enum('sexo', ['masculino', 'femenino', 'mixto']);
            $table->integer('edad_min')->nullable();
            $table->integer('edad_max')->nullable();
            $table->decimal('peso_min', 5, 2)->nullable();
            $table->decimal('peso_max', 5, 2)->nullable();
            $table->integer('grado_min')->nullable();
            $table->integer('grado_max')->nullable();
            $table->enum('estado', ['pendiente', 'en_curso', 'finalizada'])->default('pendiente');
            $table->integer('tatami_asignado')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_torneo')->references('id_torneo')->on('torneo')->onDelete('cascade');
            $table->foreign('id_categoria_def')->references('id_categoria_def')->on('categoria_definicion')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categoria_torneo');
    }
};
