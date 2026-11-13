<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('post_article_translations', 'post_article_translations_ga_views_30d_index')) {
            Schema::table('post_article_translations', fn (Blueprint $table) => $table->dropIndex('post_article_translations_ga_views_30d_index'));
        }

        $columns = array_values(array_filter(['ga_views_30d', 'ga_synced_at'], fn ($c) => Schema::hasColumn('post_article_translations', $c)));
        if ($columns !== []) {
            Schema::table('post_article_translations', fn (Blueprint $table) => $table->dropColumn($columns));
        }

        $permissionIds = DB::table('permissions')->where('name', 'post_analytics.view')->pluck('id');
        if ($permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void {}
};
