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
        Schema::table('mkt_listings', function (Blueprint $table) {
            if (!Schema::hasColumn('mkt_listings', 'posted_by')) {
                $table->unsignedBigInteger('posted_by')->default(0);
            }
            if (!Schema::hasColumn('mkt_listings', 'slug')) {
                $table->string('slug', 320)->nullable()->unique()->after('posted_by');
            }
            if (!Schema::hasColumn('mkt_listings', 'benefits')) {
                $table->text('benefits')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('mkt_listings', 'visibility')) {
                $table->string('visibility', 20)->default('public')->after('benefits');
            }
            if (!Schema::hasColumn('mkt_listings', 'salary_is_negotiable')) {
                $table->boolean('salary_is_negotiable')->default(false)->after('visibility');
            }
            if (!Schema::hasColumn('mkt_listings', 'salary_is_visible')) {
                $table->boolean('salary_is_visible')->default(true)->after('salary_is_negotiable');
            }
            if (!Schema::hasColumn('mkt_listings', 'budget_min')) {
                $table->decimal('budget_min', 15, 2)->nullable()->after('salary_is_visible');
            }
            if (!Schema::hasColumn('mkt_listings', 'budget_max')) {
                $table->decimal('budget_max', 15, 2)->nullable()->after('budget_min');
            }
            if (!Schema::hasColumn('mkt_listings', 'duration_days')) {
                $table->integer('duration_days')->nullable()->after('budget_max');
            }
            if (!Schema::hasColumn('mkt_listings', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('duration_days');
            }
            if (!Schema::hasColumn('mkt_listings', 'position_id')) {
                $table->unsignedBigInteger('position_id')->nullable()->after('department_id');
            }
            if (!Schema::hasColumn('mkt_listings', 'bookmark_count')) {
                $table->integer('bookmark_count')->default(0)->after('position_id');
            }
            if (!Schema::hasColumn('mkt_listings', 'jp_sync_status')) {
                $table->string('jp_sync_status', 20)->nullable()->after('bookmark_count');
            }
            if (!Schema::hasColumn('mkt_listings', 'auto_close_on_jp')) {
                $table->boolean('auto_close_on_jp')->default(true)->after('jp_sync_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mkt_listings', function (Blueprint $table) {
            $cols = array_filter(['posted_by', 'slug', 'benefits', 'visibility', 'salary_is_negotiable', 'salary_is_visible', 'budget_min', 'budget_max', 'duration_days', 'department_id', 'position_id', 'bookmark_count', 'jp_sync_status', 'auto_close_on_jp'], fn($c) => Schema::hasColumn('mkt_listings', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};