<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            if (!Schema::hasColumn('workflows', 'category')) {
                $table->string('category', 64)->nullable()->after('description');
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
                $table->tinyInteger('definition_status')->unsigned()->default(1)->after('tags');
                // 1=draft 2=active 3=archived
            }
            if (!Schema::hasColumn('workflows', 'version')) {
                $table->tinyInteger('version')->unsigned()->default(1)->after('definition_status');
            }
            if (!Schema::hasColumn('workflows', 'cooldown_window_min')) {
                $table->unsignedSmallInteger('cooldown_window_min')->nullable()->after('cooldown_type');
            }
            if (!Schema::hasColumn('workflows', 'cooldown_count_max')) {
                $table->tinyInteger('cooldown_count_max')->unsigned()->nullable()->after('cooldown_window_min');
            }
            if (!Schema::hasColumn('workflows', 'allowed_trigger_roles')) {
                $table->text('allowed_trigger_roles')->nullable()->after('cooldown_count_max');
            }
            if (!Schema::hasColumn('workflows', 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->after('allowed_trigger_roles');
            }
        });
        // Add category index only if it doesn't exist
        try {
            Schema::table('workflows', function (Blueprint $table) {
                $table->index(['organization_id', 'category'], 'idx_org_category');
            });
        } catch (\Throwable) {}
    }

    public function down(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            $cols = ['category','icon','color','tags','definition_status','version',
                     'cooldown_window_min','cooldown_count_max','allowed_trigger_roles','template_id'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('workflows', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
