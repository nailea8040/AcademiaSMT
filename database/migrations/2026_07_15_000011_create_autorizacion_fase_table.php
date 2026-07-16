<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autorizacion_fase', function (Blueprint $table) {
            $table->id('id_autorizacion');
            $table->unsignedBigInteger('id_torneo');
            $table->enum('fase', ['graficacion', 'mesas', 'premiacion', 'memoria']);
            $table->integer('id_usuario_autoriza');
            $table->string('nip_hash', 255);
            $table->timestamp('fecha_autorizacion')->useCurrent();
            $table->string('ip_address', 45)->nullable();

            $table->foreign('id_torneo')->references('id_torneo')->on('torneo')->onDelete('cascade');
            $table->foreign('id_usuario_autoriza')->references('id_usuario')->on('usuario')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autorizacion_fase');
    }
};
