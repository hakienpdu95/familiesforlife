<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 14 — Performance indexes per spec section 12.
 * Adds indexes missing from initial migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // idx_raci_bulk — single-query RACI fetch per batch of steps
        if (! $this->indexExists('sop_step_raci', 'idx_raci_bulk')) {
            DB::statement('CREATE INDEX idx_raci_bulk ON sop_step_raci(step_id, raci_type)');
        }

        // idx_step_analytics — AVG(duration) GROUP BY step_type analytics query
        if (! $this->indexExists('sop_steps', 'idx_step_analytics')) {
            if ($driver === 'sqlite') {
                // SQLite supports partial indexes natively
                DB::statement('CREATE INDEX idx_step_analytics ON sop_steps(step_type, is_active, duration_minutes) WHERE is_active = 1 AND duration_minutes IS NOT NULL');
            } else {
                // MySQL 8+ does not support partial indexes — use covering index without WHERE
                DB::statement('CREATE INDEX idx_step_analytics ON sop_steps(step_type, is_active, duration_minutes)');
            }
        }

        // idx_sop_proc_org_status — covers SOP list page (org + status + created_at sort)
        if (! $this->indexExists('sop_processes', 'idx_sop_proc_org_status')) {
            if ($driver === 'sqlite') {
                DB::statement('CREATE INDEX idx_sop_proc_org_status ON sop_processes(organization_id, status, created_at)');
            } else {
                // MySQL supports DESC on index columns (8.0+)
                DB::statement('CREATE INDEX idx_sop_proc_org_status ON sop_processes(organization_id, status, created_at DESC)');
            }
        }
    }

    public function down(): void
    {
        if ($this->indexExists('sop_step_raci', 'idx_raci_bulk')) {
            Schema::table('sop_step_raci', fn ($t) => $t->dropIndex('idx_raci_bulk'));
        }

        if ($this->indexExists('sop_steps', 'idx_step_analytics')) {
            Schema::table('sop_steps', fn ($t) => $t->dropIndex('idx_step_analytics'));
        }

        if ($this->indexExists('sop_processes', 'idx_sop_proc_org_status')) {
            Schema::table('sop_processes', fn ($t) => $t->dropIndex('idx_sop_proc_org_status'));
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $result = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=? AND name=?", [$table, $indexName]);
            return count($result) > 0;
        }

        // MySQL / PostgreSQL
        $result = DB::select(
            "SELECT COUNT(*) as cnt FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?",
            [$table, $indexName]
        );
        return ($result[0]->cnt ?? 0) > 0;
    }
};
