<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 14 — BR-FC-001: DB-level guard against connectors pointing to inactive steps.
 * MySQL only — SQLite skipped (validation handled at app layer in StoreSopStepConnectorAction).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Drop if exists from a previous failed migration
        DB::unprepared('DROP TRIGGER IF EXISTS trg_connector_check_active');

        DB::unprepared("
            CREATE TRIGGER trg_connector_check_active
            BEFORE INSERT ON sop_step_connectors
            FOR EACH ROW
            BEGIN
                IF (SELECT is_active FROM sop_steps WHERE id = NEW.from_step_id) = 0 THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Cannot create connector: from_step is inactive';
                END IF;
                IF (SELECT is_active FROM sop_steps WHERE id = NEW.to_step_id) = 0 THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Cannot create connector: to_step is inactive';
                END IF;
            END
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS trg_connector_check_active');
    }
};
