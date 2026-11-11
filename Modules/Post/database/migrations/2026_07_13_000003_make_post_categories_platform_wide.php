<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('post_categories', 'organization_id')) {
            return;
        }

        if (collect(Schema::getForeignKeys('post_categories'))->contains(fn ($fk) => $fk['columns'] === ['organization_id'])) {
            Schema::table('post_categories', function (Blueprint $table) {
                $table->dropForeign(['organization_id']);
            });
        }

        $hasOrgIndex = fn (string $name) => collect(Schema::getIndexes('post_categories'))
            ->contains(fn ($idx) => $idx['name'] === $name && in_array('organization_id', $idx['columns'], true));

        if ($hasOrgIndex('uq_post_cat_org_slug')) {
            Schema::table('post_categories', function (Blueprint $table) {
                $table->dropUnique('uq_post_cat_org_slug');
            });
        }
        if ($hasOrgIndex('idx_post_cat_sort')) {
            Schema::table('post_categories', function (Blueprint $table) {
                $table->dropIndex('idx_post_cat_sort');
            });
        }
        if ($hasOrgIndex('idx_post_cat_active')) {
            Schema::table('post_categories', function (Blueprint $table) {
                $table->dropIndex('idx_post_cat_active');
            });
        }

        Schema::table('post_categories', function (Blueprint $table) {
            $table->dropColumn('organization_id');
        });

        if (! Schema::hasIndex('post_categories', 'uq_post_cat_slug')) {
            Schema::table('post_categories', function (Blueprint $table) {
                $table->unique('slug', 'uq_post_cat_slug');
            });
        }
        if (! Schema::hasIndex('post_categories', 'idx_post_cat_sort')) {
            Schema::table('post_categories', function (Blueprint $table) {
                $table->index(['parent_id', 'sort_order'], 'idx_post_cat_sort');
            });
        }
        if (! Schema::hasIndex('post_categories', 'idx_post_cat_active')) {
            Schema::table('post_categories', function (Blueprint $table) {
                $table->index(['is_active'], 'idx_post_cat_active');
            });
        }
    }

    public function down(): void {}
};
