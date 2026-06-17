<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración correctiva: arregla foreign keys que apuntan a columnas incorrectas.
 *
 * PROBLEMA:
 *   - pago.id_usuario → usuario.id (la tabla usuario usa id_usuario, no id)
 *   - Otras tablas referencian usuario sin FK definida
 *
 * SOLUCIÓN:
 *   1. Dropea la FK incorrecta en pago (si existe)
 *   2. Agrega la FK correcta: pago.id_usuario → usuario.id_usuario
 *   3. Agrega FKs faltantes en tablas que referencian usuario
 *   4. Agrega FK para historial_grados → grado
 *
 * NOTA: Esta migración usa SQL raw porque Schema::table no soporta
 *       DROP FOREIGN KEY con el builder de Laravel de forma portable.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver !== 'mysql') {
            return; // Solo aplica a MySQL
        }

        // ── 1. CORREGIR FOREIGN KEY en tabla pago ────────────────────────────
        // Buscar el nombre real de la FK constraint en la BD
        $fkPago = DB::select("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pago'
              AND COLUMN_NAME = 'id_usuario'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ");

        if (!empty($fkPago)) {
            $constraintName = $fkPago[0]->CONSTRAINT_NAME;
            DB::unprepared("ALTER TABLE `pago` DROP FOREIGN KEY `{$constraintName}`");
        }

        // Agregar la FK correcta: pago.id_usuario → usuario.id_usuario
        // with onDelete cascade para integridad referencial
        $existeFK = DB::select("
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pago'
              AND COLUMN_NAME = 'id_usuario'
              AND REFERENCED_TABLE_NAME = 'usuario'
              AND REFERENCED_COLUMN_NAME = 'id_usuario'
        ");

        if ($existeFK[0]->total == 0) {
            DB::unprepared("
                ALTER TABLE `pago`
                ADD CONSTRAINT `pago_id_usuario_foreign`
                FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
                ON DELETE CASCADE
            ");
        }

        // ── 2. FOREIGN KEY para alumno.id_usuario → usuario.id_usuario ───────
        $existeFKAlumno = DB::select("
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'alumno'
              AND COLUMN_NAME = 'id_usuario'
              AND REFERENCED_TABLE_NAME = 'usuario'
              AND REFERENCED_COLUMN_NAME = 'id_usuario'
        ");

        if ($existeFKAlumno[0]->total == 0) {
            DB::unprepared("
                ALTER TABLE `alumno`
                ADD CONSTRAINT `alumno_id_usuario_foreign`
                FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
                ON DELETE CASCADE
            ");
        }

        // ── 3. FOREIGN KEY para tutor.id_Tutor → usuario.id_usuario ──────────
        $existeFKTutor = DB::select("
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'tutor'
              AND COLUMN_NAME = 'id_Tutor'
              AND REFERENCED_TABLE_NAME = 'usuario'
              AND REFERENCED_COLUMN_NAME = 'id_usuario'
        ");

        if ($existeFKTutor[0]->total == 0) {
            DB::unprepared("
                ALTER TABLE `tutor`
                ADD CONSTRAINT `tutor_id_tutor_foreign`
                FOREIGN KEY (`id_Tutor`) REFERENCES `usuario` (`id_usuario`)
                ON DELETE CASCADE
            ");
        }

        // ── 4. FOREIGN KEY para historial_grados ─────────────────────────────
        // historial_grados.id_usuario → usuario.id_usuario
        $existeFKHistUsr = DB::select("
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'historial_grados'
              AND COLUMN_NAME = 'id_usuario'
              AND REFERENCED_TABLE_NAME = 'usuario'
              AND REFERENCED_COLUMN_NAME = 'id_usuario'
        ");

        if ($existeFKHistUsr[0]->total == 0) {
            DB::unprepared("
                ALTER TABLE `historial_grados`
                ADD CONSTRAINT `historialgrados_id_usuario_foreign`
                FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
                ON DELETE CASCADE
            ");
        }

        // historial_grados.id_grado → grado.id_grado
        $existeFKHistGrado = DB::select("
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'historial_grados'
              AND COLUMN_NAME = 'id_grado'
              AND REFERENCED_TABLE_NAME = 'grado'
              AND REFERENCED_COLUMN_NAME = 'id_grado'
        ");

        if ($existeFKHistGrado[0]->total == 0) {
            DB::unprepared("
                ALTER TABLE `historial_grados`
                ADD CONSTRAINT `historialgrados_id_grado_foreign`
                FOREIGN KEY (`id_grado`) REFERENCES `grado` (`id_grado`)
                ON DELETE RESTRICT
            ");
        }

        // ── 5. FOREIGN KEY para asistencia.id_usuario → usuario.id_usuario ───
        $existeFKAsist = DB::select("
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'asistencia'
              AND COLUMN_NAME = 'id_usuario'
              AND REFERENCED_TABLE_NAME = 'usuario'
              AND REFERENCED_COLUMN_NAME = 'id_usuario'
        ");

        if ($existeFKAsist[0]->total == 0) {
            DB::unprepared("
                ALTER TABLE `asistencia`
                ADD CONSTRAINT `asistencia_id_usuario_foreign`
                FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
                ON DELETE CASCADE
            ");
        }

        // ── 6. FOREIGN KEY para calendario.id_usuario → usuario.id_usuario ───
        $existeFKCal = DB::select("
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'calendario'
              AND COLUMN_NAME = 'id_usuario'
              AND REFERENCED_TABLE_NAME = 'usuario'
              AND REFERENCED_COLUMN_NAME = 'id_usuario'
        ");

        if ($existeFKCal[0]->total == 0) {
            DB::unprepared("
                ALTER TABLE `calendario`
                ADD CONSTRAINT `calendario_id_usuario_foreign`
                FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
                ON DELETE CASCADE
            ");
        }

        // ── 7. FOREIGN KEY para evento.id_usuario → usuario.id_usuario ───────
        $existeFKEvento = DB::select("
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'evento'
              AND COLUMN_NAME = 'id_usuario'
              AND REFERENCED_TABLE_NAME = 'usuario'
              AND REFERENCED_COLUMN_NAME = 'id_usuario'
        ");

        if ($existeFKEvento[0]->total == 0) {
            DB::unprepared("
                ALTER TABLE `evento`
                ADD CONSTRAINT `evento_id_usuario_foreign`
                FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
                ON DELETE CASCADE
            ");
        }

        // ── 8. FOREIGN KEY para galeria.id_usuario → usuario.id_usuario ──────
        $existeFKGal = DB::select("
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'galeria'
              AND COLUMN_NAME = 'id_usuario'
              AND REFERENCED_TABLE_NAME = 'usuario'
              AND REFERENCED_COLUMN_NAME = 'id_usuario'
        ");

        if ($existeFKGal[0]->total == 0) {
            DB::unprepared("
                ALTER TABLE `galeria`
                ADD CONSTRAINT `galeria_id_usuario_foreign`
                FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
                ON DELETE CASCADE
            ");
        }

        // ── 9. FOREIGN KEY para abono.id_pago → pago.id_pago ────────────────
        $existeFKAbono = DB::select("
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'abono'
              AND COLUMN_NAME = 'id_pago'
              AND REFERENCED_TABLE_NAME = 'pago'
              AND REFERENCED_COLUMN_NAME = 'id_pago'
        ");

        if ($existeFKAbono[0]->total == 0) {
            DB::unprepared("
                ALTER TABLE `abono`
                ADD CONSTRAINT `abono_id_pago_foreign`
                FOREIGN KEY (`id_pago`) REFERENCES `pago` (`id_pago`)
                ON DELETE CASCADE
            ");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'mysql') return;

        // Reverse: dropear las FK agregadas (en orden inverso)
        $constraints = [
            'abono_id_pago_foreign',
            'galeria_id_usuario_foreign',
            'evento_id_usuario_foreign',
            'calendario_id_usuario_foreign',
            'asistencia_id_usuario_foreign',
            'historialgrados_id_grado_foreign',
            'historialgrados_id_usuario_foreign',
            'tutor_id_tutor_foreign',
            'alumno_id_usuario_foreign',
            'pago_id_usuario_foreign',
        ];

        foreach ($constraints as $fk) {
            $exists = DB::select("
                SELECT COUNT(*) AS total
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND CONSTRAINT_NAME = '{$fk}'
            ");

            if ($exists[0]->total > 0) {
                // Necesitamos saber a qué tabla pertenece
                $table = DB::select("
                    SELECT TABLE_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND CONSTRAINT_NAME = '{$fk}'
                    LIMIT 1
                ");

                if (!empty($table)) {
                    DB::unprepared("ALTER TABLE `{$table[0]->TABLE_NAME}` DROP FOREIGN KEY `{$fk}`");
                }
            }
        }
    }
};
