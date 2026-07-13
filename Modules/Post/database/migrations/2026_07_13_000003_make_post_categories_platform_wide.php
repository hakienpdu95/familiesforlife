<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Platform_RBAC_Phase2_Specification.md §4 (v3.0) — category dùng chung toàn nền tảng,
 * do đội biên tập Platform tự quản lý, không phụ thuộc tổ chức nào. Khác các bảng Post khác ở
 * §3.3 (drop hẳn organization_id) — category GIỮ cột này nhưng chuyển nullable, vì đây là nơi
 * duy nhất trong Post còn hợp lý để biết "chuyên mục này ai tạo/thuộc về đâu" cho mục đích quản
 * trị (post_category_editors ở migration sau gán biên tập viên theo category, không theo tổ
 * chức). Với 0 dòng dữ liệu hiện tại, không cần data-fix.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (collect(Schema::getForeignKeys('post_categories'))->contains(fn ($fk) => $fk['columns'] === ['organization_id'])) {
            Schema::table('post_categories', function (Blueprint $table) {
                $table->dropForeign(['organization_id']);
            });
        }

        if (collect(Schema::getIndexes('post_categories'))->contains('name', 'uq_post_cat_org_slug')) {
            Schema::table('post_categories', function (Blueprint $table) {
                $table->dropUnique('uq_post_cat_org_slug');
            });
        }
        if (collect(Schema::getIndexes('post_categories'))->contains('name', 'idx_post_cat_sort')) {
            Schema::table('post_categories', function (Blueprint $table) {
                $table->dropIndex('idx_post_cat_sort');
            });
        }
        if (collect(Schema::getIndexes('post_categories'))->contains('name', 'idx_post_cat_active')) {
            Schema::table('post_categories', function (Blueprint $table) {
                $table->dropIndex('idx_post_cat_active');
            });
        }

        Schema::table('post_categories', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->change();
        });

        Schema::table('post_categories', function (Blueprint $table) {
            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
        });

        if (! collect(Schema::getIndexes('post_categories'))->contains('name', 'uq_post_cat_slug')) {
            Schema::table('post_categories', function (Blueprint $table) {
                $table->unique('slug', 'uq_post_cat_slug');
            });
        }
        if (! collect(Schema::getIndexes('post_categories'))->contains('name', 'idx_post_cat_sort')) {
            Schema::table('post_categories', function (Blueprint $table) {
                $table->index(['parent_id', 'sort_order'], 'idx_post_cat_sort');
            });
        }
        if (! collect(Schema::getIndexes('post_categories'))->contains('name', 'idx_post_cat_active')) {
            Schema::table('post_categories', function (Blueprint $table) {
                $table->index(['is_active'], 'idx_post_cat_active');
            });
        }
    }

    public function down(): void
    {
        Schema::table('post_categories', function (Blueprint $table) {
            $table->dropUnique('uq_post_cat_slug');
            $table->dropIndex('idx_post_cat_sort');
            $table->dropIndex('idx_post_cat_active');
            $table->dropForeign(['organization_id']);
        });

        Schema::table('post_categories', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable(false)->change();
        });

        Schema::table('post_categories', function (Blueprint $table) {
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->unique(['organization_id', 'slug'], 'uq_post_cat_org_slug');
            $table->index(['organization_id', 'parent_id', 'sort_order'], 'idx_post_cat_sort');
            $table->index(['organization_id', 'is_active'], 'idx_post_cat_active');
        });
    }
};
