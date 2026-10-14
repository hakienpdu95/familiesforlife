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
        Schema::table('workflows', function (Blueprint $table) {
            if (!Schema::hasColumn('workflows', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->comment('Thời gian xóa mềm');
            }
            if (!Schema::hasColumn('workflows', 'category')) {
                $table->string('category', 64)->nullable()->after('deleted_at');
            }
            if (!Schema::hasColumn('workflows', 'icon')) {
                $table->string('icon', 64)->nullable()->after('category');
            }
            if (!Schema::hasColumn('workflows', 'color')) {
                $table->string('color', 7)->nullable()->after('icon');
            }
            if (!Schema::hasColumn('workflows', 'tags')) {
                $table->text('tags')->nullable()->after('color');
            }
            if (!Schema::hasColumn('workflows', 'definition_status')) {
                $table->unsignedTinyInteger('definition_status')->default(1)->after('tags');
            }
            if (!Schema::hasColumn('workflows', 'version')) {
                $table->unsignedTinyInteger('version')->default(1)->after('definition_status');
            }
            if (!Schema::hasColumn('workflows', 'cooldown_window_min')) {
                $table->unsignedSmallInteger('cooldown_window_min')->nullable()->after('version');
            }
            if (!Schema::hasColumn('workflows', 'cooldown_count_max')) {
                $table->unsignedTinyInteger('cooldown_count_max')->nullable()->after('cooldown_window_min');
            }
            if (!Schema::hasColumn('workflows', 'allowed_trigger_roles')) {
                $table->text('allowed_trigger_roles')->nullable()->after('cooldown_count_max');
            }
            if (!Schema::hasColumn('workflows', 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->after('allowed_trigger_roles');
            }
            if (!Schema::hasIndex('workflows', 'idx_org_category')) {
                $table->index(['organization_id', 'category'], 'idx_org_category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            $cols = array_filter(['deleted_at', 'category', 'icon', 'color', 'tags', 'definition_status', 'version', 'cooldown_window_min', 'cooldown_count_max', 'allowed_trigger_roles', 'template_id'], fn($c) => Schema::hasColumn('workflows', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};