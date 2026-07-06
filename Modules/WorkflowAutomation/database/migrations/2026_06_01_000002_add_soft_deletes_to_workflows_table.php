<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Workflow extends TenantAwareModel (uses SoftDeletes), but the original
        // table was created without a deleted_at column — every query against it
        // fails. Add the missing column. Additive, nullable — no data loss.
        Schema::table('workflows', function (Blueprint $table) {
            if (!Schema::hasColumn('workflows', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
