<?php
// database/migrations/xxxx_xx_xx_add_mercadopago_to_pago_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pago', function (Blueprint $table) {
            // ID de la preferencia creada en MercadoPago
            $table->string('mp_preference_id', 100)->nullable()->after('referencia_pago');
            // ID del pago devuelto por el webhook de MP (payment_id)
            $table->string('mp_payment_id', 100)->nullable()->after('mp_preference_id');
            // Estado que devuelve MP: approved, pending, rejected, cancelled
            $table->string('mp_status', 30)->nullable()->after('mp_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('pago', function (Blueprint $table) {
            $table->dropColumn(['mp_preference_id', 'mp_payment_id', 'mp_status']);
        });
    }
};