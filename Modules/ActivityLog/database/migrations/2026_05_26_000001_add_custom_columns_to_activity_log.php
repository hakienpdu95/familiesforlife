<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->unsignedTinyInteger('level')
                  ->default(2)->after('log_name')
                  ->comment('1=debug 2=info 3=warning 4=error 5=critical');

            $table->unsignedBigInteger('organization_id')
                  ->nullable()->after('level')
                  ->comment('Tenant context — NULL khi CLI/system job');

            $table->string('module', 64)->nullable()->after('organization_id');
            $table->string('action', 128)->nullable()->after('module');

            $table->string('actor_name', 255)->nullable()->after('action')
                  ->comment('Snapshot tên actor tại thời điểm log');

            $table->string('actor_ip', 45)->nullable()->after('actor_name')
                  ->comment('IPv4 hoặc IPv6');

            $table->char('request_id', 36)->nullable()->after('actor_ip');
            $table->string('session_id', 255)->nullable()->after('request_id');

            $table->string('subject_label', 255)->nullable()->after('session_id')
                  ->comment('Snapshot label của subject (name/title/email)');

            $table->index(['organization_id', 'created_at'], 'idx_org_created');
            $table->index(['level', 'created_at'],            'idx_level');
            $table->index(['module', 'action', 'created_at'], 'idx_module_action');
            $table->index('request_id',                       'idx_request');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('idx_org_created');
            $table->dropIndex('idx_level');
            $table->dropIndex('idx_module_action');
            $table->dropIndex('idx_request');
            $table->dropColumn([
                'level', 'organization_id', 'module', 'action',
                'actor_name', 'actor_ip', 'request_id', 'session_id', 'subject_label',
            ]);
        });
    }
};
