<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_log', 'level')) {
                $table->unsignedTinyInteger('level')->default(2)->comment('1=debug 2=info 3=warning 4=error 5=critical');
            }
            if (!Schema::hasColumn('activity_log', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('level')->comment('Tenant context — NULL khi CLI/system job');
            }
            if (!Schema::hasColumn('activity_log', 'module')) {
                $table->string('module', 64)->nullable()->after('organization_id');
            }
            if (!Schema::hasColumn('activity_log', 'action')) {
                $table->string('action', 128)->nullable()->after('module');
            }
            if (!Schema::hasColumn('activity_log', 'actor_name')) {
                $table->string('actor_name', 255)->nullable()->after('action')->comment('Snapshot tên actor tại thời điểm log');
            }
            if (!Schema::hasColumn('activity_log', 'actor_ip')) {
                $table->string('actor_ip', 45)->nullable()->after('actor_name')->comment('IPv4 hoặc IPv6');
            }
            if (!Schema::hasColumn('activity_log', 'request_id')) {
                $table->char('request_id', 36)->nullable()->after('actor_ip');
            }
            if (!Schema::hasColumn('activity_log', 'session_id')) {
                $table->string('session_id', 255)->nullable()->after('request_id');
            }
            if (!Schema::hasColumn('activity_log', 'subject_label')) {
                $table->string('subject_label', 255)->nullable()->after('session_id')->comment('Snapshot label của subject (name/title/email)');
            }
            if (!Schema::hasIndex('activity_log', 'idx_org_created')) {
                $table->index(['organization_id', 'created_at'], 'idx_org_created');
            }
            if (!Schema::hasIndex('activity_log', 'idx_level')) {
                $table->index(['level', 'created_at'], 'idx_level');
            }
            if (!Schema::hasIndex('activity_log', 'idx_module_action')) {
                $table->index(['module', 'action', 'created_at'], 'idx_module_action');
            }
            if (!Schema::hasIndex('activity_log', 'idx_request')) {
                $table->index('request_id', 'idx_request');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $cols = array_filter(['level', 'organization_id', 'module', 'action', 'actor_name', 'actor_ip', 'request_id', 'session_id', 'subject_label'], fn($c) => Schema::hasColumn('activity_log', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};