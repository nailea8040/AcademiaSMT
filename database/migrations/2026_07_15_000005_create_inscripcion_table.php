<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripcion', function (Blueprint $table) {
            $table->id('id_inscripcion');
            $table->unsignedBigInteger('id_torneo');
            $table->unsignedBigInteger('id_categoria_torneo');
            $table->timestamp('fecha_inscripcion')->useCurrent();
            $table->enum('estado', ['activa', 'retirada', 'descalificada'])->default('activa');

            $table->string('nombre_completo', 300);
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('genero', ['masculino', 'femenino'])->nullable();
            $table->string('grado_cinta', 100)->nullable();
            $table->decimal('peso', 5, 2)->nullable();
            $table->string('dojo_procedencia', 200)->nullable();
            $table->string('maestro_cargo', 300)->nullable();
            $table->enum('disciplina_inscrita', ['kata', 'kumite', 'ambas']);

            $table->foreign('id_torneo')->references('id_torneo')->on('torneo')->onDelete('cascade');
            $table->foreign('id_categoria_torneo')->references('id_categoria_torneo')->on('categoria_torneo')->onDelete('cascade');
            $table->unique(['id_torneo', 'id_categoria_torneo', 'nombre_completo'], 'unique_inscripcion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripcion');
    }
};
