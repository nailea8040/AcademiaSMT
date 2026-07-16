<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_fase', function (Blueprint $table) {
            $table->id('id_log');
            $table->unsignedBigInteger('id_torneo');
            $table->enum('fase_anterior', ['borrador', 'inscripcion', 'graficacion', 'mesas', 'premiacion', 'memoria', 'finalizado'])->nullable();
            $table->enum('fase_nueva', ['borrador', 'inscripcion', 'graficacion', 'mesas', 'premiacion', 'memoria', 'finalizado']);
            $table->integer('id_usuario');
            $table->unsignedBigInteger('id_autorizacion')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_torneo')->references('id_torneo')->on('torneo')->onDelete('cascade');
            $table->foreign('id_usuario')->references('id_usuario')->on('usuario')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_fase');
    }
};
