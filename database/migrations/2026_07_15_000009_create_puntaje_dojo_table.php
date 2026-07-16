<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puntaje_dojo', function (Blueprint $table) {
            $table->id('id_puntaje');
            $table->unsignedBigInteger('id_torneo');
            $table->string('dojo_nombre', 200);
            $table->integer('puntos_1ro')->default(0);
            $table->integer('puntos_2do')->default(0);
            $table->integer('puntos_3ro')->default(0);
            $table->integer('total_puntos')->storedAs('(`puntos_1ro` * 100) + (`puntos_2do` * 75) + (`puntos_3ro` * 50)');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_torneo')->references('id_torneo')->on('torneo')->onDelete('cascade');
            $table->unique(['id_torneo', 'dojo_nombre'], 'unique_dojo_torneo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puntaje_dojo');
    }
};
