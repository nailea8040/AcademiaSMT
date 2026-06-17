<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración correctiva: sincroniza la tabla 'usuario' con el modelo Usuario.
 *
 * PROBLEMA:
 *   La migración original (2026_03_09_221654) crea:
 *     id, nombre, apaterno, amaterno, fecha_naci, tel, correo, pass, timestamps
 *
 *   Pero el modelo usa:
 *     id_usuario (PK), nombre, apaterno, amaterno, fecha_naci, telefono,
 *     correo, pass, rol, estado, fecha_registro, avatar, numero_control,
 *     grupo, especialidad, turno, token_recuperacion, token_expiracion,
 *     ultima_solicitud_token
 *
 * SOLUCIÓN:
 *   1. Renombrar 'tel' → 'telefono' (si existe)
 *   2. Agregar columnas faltantes si no existen
 *   3. No tocar datos existentes
 *
 * NOTA: Esta migración es idempotente — puede ejecutarse múltiples veces sin error.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'mysql') return;

        // ── Helper: verificar si una columna existe ──────────────────────────
        $columnExists = function (string $table, string $column): bool {
            $result = DB::select("
                SELECT COUNT(*) AS total
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = '{$table}'
                  AND COLUMN_NAME = '{$column}'
            ");
            return $result[0]->total > 0;
        };

        // ── 1. Renombrar 'tel' → 'telefono' ────────────────────────────────
        if ($columnExists('usuario', 'tel') && !$columnExists('usuario', 'telefono')) {
            DB::unprepared("ALTER TABLE `usuario` CHANGE `tel` `telefono` VARCHAR(20) NULL");
        }

        // ── 2. Agregar 'rol' (ENUM) ────────────────────────────────────────
        if (!$columnExists('usuario', 'rol')) {
            DB::unprepared("
                ALTER TABLE `usuario`
                ADD COLUMN `rol` ENUM('admin','sensei','alumno','tutor') NOT NULL DEFAULT 'alumno'
                AFTER `pass`
            ");
        }

        // ── 3. Agregar 'estado' (TINYINT, default 1) ───────────────────────
        if (!$columnExists('usuario', 'estado')) {
            DB::unprepared("
                ALTER TABLE `usuario`
                ADD COLUMN `estado` TINYINT(1) NOT NULL DEFAULT 1
                AFTER `rol`
            ");
        }

        // ── 4. Agregar 'fecha_registro' (DATE) ─────────────────────────────
        if (!$columnExists('usuario', 'fecha_registro')) {
            DB::unprepared("
                ALTER TABLE `usuario`
                ADD COLUMN `fecha_registro` DATE NULL
                AFTER `estado`
            ");
        }

        // ── 5. Agregar 'avatar' (VARCHAR, nullable) ────────────────────────
        if (!$columnExists('usuario', 'avatar')) {
            DB::unprepared("
                ALTER TABLE `usuario`
                ADD COLUMN `avatar` VARCHAR(255) NULL
                AFTER `fecha_registro`
            ");
        }

        // ── 6. Agregar 'token_recuperacion' (VARCHAR, nullable) ────────────
        if (!$columnExists('usuario', 'token_recuperacion')) {
            DB::unprepared("
                ALTER TABLE `usuario`
                ADD COLUMN `token_recuperacion` VARCHAR(255) NULL
                AFTER `avatar`
            ");
        }

        // ── 7. Agregar 'token_expiracion' (TIMESTAMP, nullable) ────────────
        if (!$columnExists('usuario', 'token_expiracion')) {
            DB::unprepared("
                ALTER TABLE `usuario`
                ADD COLUMN `token_expiracion` TIMESTAMP NULL
                AFTER `token_recuperacion`
            ");
        }

        // ── 8. Agregar 'ultima_solicitud_token' (TIMESTAMP, nullable) ──────
        if (!$columnExists('usuario', 'ultima_solicitud_token')) {
            DB::unprepared("
                ALTER TABLE `usuario`
                ADD COLUMN `ultima_solicitud_token` TIMESTAMP NULL
                AFTER `token_expiracion`
            ");
        }

        // ── 9. Agregar índice en 'correo' si no existe ─────────────────────
        $indiceExiste = DB::select("
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'usuario'
              AND COLUMN_NAME = 'correo'
              AND NON_UNIQUE = 0
        ");

        if ($indiceExiste[0]->total == 0) {
            DB::unprepared("ALTER TABLE `usuario` ADD UNIQUE INDEX `correo_unique` (`correo`)");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'mysql') return;

        $columnExists = function (string $table, string $column): bool {
            $result = DB::select("
                SELECT COUNT(*) AS total
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = '{$table}'
                  AND COLUMN_NAME = '{$column}'
            ");
            return $result[0]->total > 0;
        };

        // Revertir en orden inverso
        $columnsToDrop = [
            'ultima_solicitud_token',
            'token_expiracion',
            'token_recuperacion',
            'avatar',
            'fecha_registro',
            'estado',
            'rol',
        ];

        foreach ($columnsToDrop as $col) {
            if ($columnExists('usuario', $col)) {
                DB::unprepared("ALTER TABLE `usuario` DROP COLUMN `{$col}`");
            }
        }

        // Renombrar 'telefono' → 'tel' si existe
        if ($columnExists('usuario', 'telefono') && !$columnExists('usuario', 'tel')) {
            DB::unprepared("ALTER TABLE `usuario` CHANGE `telefono` `tel` VARCHAR(20) NULL");
        }
    }
};
