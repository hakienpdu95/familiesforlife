<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workflow_executions', function (Blueprint $table) {
            // Compact snapshot of the trigger payload (extra + subject attributes) for
            // traceability in the execution detail view. Additive, nullable — no data loss.
            $table->json('context')->nullable()->after('actor_id');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_executions', function (Blueprint $table) {
            $table->dropColumn('context');
        });
    }
};
