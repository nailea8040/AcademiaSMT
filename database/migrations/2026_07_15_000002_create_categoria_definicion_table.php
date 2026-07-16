<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categoria_definicion', function (Blueprint $table) {
            $table->id('id_categoria_def');
            $table->unsignedBigInteger('id_plantilla');
            $table->string('nombre_categoria', 150);
            $table->enum('tipo_disciplina', ['kata', 'kumite', 'ambas']);
            $table->enum('sexo', ['masculino', 'femenino', 'mixto']);
            $table->integer('edad_min')->nullable();
            $table->integer('edad_max')->nullable();
            $table->decimal('peso_min', 5, 2)->nullable();
            $table->decimal('peso_max', 5, 2)->nullable();
            $table->integer('grado_min')->nullable();
            $table->integer('grado_max')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_plantilla')->references('id_plantilla')->on('plantilla_categoria')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categoria_definicion');
    }
};
