<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Platform_RBAC_Phase2_Specification.md §3.3 mục 7 (v3.0) — 4 bảng con denormalize
 * organization_id từ post_articles (thuần tuý để OrganizationScope lọc trực tiếp không cần
 * join — spec/PublishingEngine_Technical_Specification.md §3.2). Post không còn tenant-scoped
 * nên cả 4 bảng bỏ hẳn cột này, không thay thế bằng gì khác.
 */
return new class extends Migration
{
    /**
     * Mỗi bước guard bằng hasColumn/getIndexes/getForeignKeys — cho phép chạy lại an toàn nếu
     * 1 statement giữa chừng lỗi ở lần chạy trước (MySQL DDL tự commit từng statement, không
     * rollback theo transaction bao ngoài — đúng pattern đã dùng ở
     * 2026_07_11_000003_finalize_post_translations_schema.php).
     */
    public function up(): void
    {
        // post_article_translations — slug giờ unique toàn cục theo locale, không còn theo tổ chức.
        // Drop FK TRƯỚC — MySQL từ chối drop index khi nó còn đang hỗ trợ 1 FK constraint.
        if (collect(Schema::getForeignKeys('post_article_translations'))->contains(fn ($fk) => $fk['columns'] === ['organization_id'])) {
            Schema::table('post_article_translations', function (Blueprint $table) {
                $table->dropForeign(['organization_id']);
            });
        }
        if (collect(Schema::getIndexes('post_article_translations'))->contains('name', 'uq_post_trans_org_locale_slug')) {
            Schema::table('post_article_translations', function (Blueprint $table) {
                $table->dropUnique('uq_post_trans_org_locale_slug');
            });
        }
        if (collect(Schema::getIndexes('post_article_translations'))->contains('name', 'idx_post_trans_org_status_pub')) {
            Schema::table('post_article_translations', function (Blueprint $table) {
                $table->dropIndex('idx_post_trans_org_status_pub');
            });
        }
        if (Schema::hasColumn('post_article_translations', 'organization_id')) {
            Schema::table('post_article_translations', function (Blueprint $table) {
                $table->dropColumn('organization_id');
            });
        }
        if (! collect(Schema::getIndexes('post_article_translations'))->contains('name', 'uq_post_trans_locale_slug')) {
            Schema::table('post_article_translations', function (Blueprint $table) {
                $table->unique(['locale', 'slug'], 'uq_post_trans_locale_slug');
            });
        }
        if (! collect(Schema::getIndexes('post_article_translations'))->contains('name', 'idx_post_trans_status_pub')) {
            Schema::table('post_article_translations', function (Blueprint $table) {
                $table->index(['locale', 'status', 'published_at'], 'idx_post_trans_status_pub');
            });
        }

        // post_content_blocks — không có index riêng nào khoá theo organization_id còn lại.
        if (Schema::hasColumn('post_content_blocks', 'organization_id')) {
            if (collect(Schema::getForeignKeys('post_content_blocks'))->contains(fn ($fk) => $fk['columns'] === ['organization_id'])) {
                Schema::table('post_content_blocks', function (Blueprint $table) {
                    $table->dropForeign(['organization_id']);
                });
            }
            Schema::table('post_content_blocks', function (Blueprint $table) {
                $table->dropColumn('organization_id');
            });
        }

        // post_product_blocks
        if (collect(Schema::getForeignKeys('post_product_blocks'))->contains(fn ($fk) => $fk['columns'] === ['organization_id'])) {
            Schema::table('post_product_blocks', function (Blueprint $table) {
                $table->dropForeign(['organization_id']);
            });
        }
        if (collect(Schema::getIndexes('post_product_blocks'))->contains('name', 'idx_post_pb_org_translation')) {
            Schema::table('post_product_blocks', function (Blueprint $table) {
                $table->dropIndex('idx_post_pb_org_translation');
            });
        }
        if (Schema::hasColumn('post_product_blocks', 'organization_id')) {
            Schema::table('post_product_blocks', function (Blueprint $table) {
                $table->dropColumn('organization_id');
            });
        }
        if (! collect(Schema::getIndexes('post_product_blocks'))->contains('name', 'idx_post_pb_translation')) {
            Schema::table('post_product_blocks', function (Blueprint $table) {
                $table->index(['translation_id'], 'idx_post_pb_translation');
            });
        }

        // post_publishing_logs — chỉ set 1 chiều, không nơi nào đọc lại, an toàn để drop.
        if (Schema::hasColumn('post_publishing_logs', 'organization_id')) {
            if (collect(Schema::getForeignKeys('post_publishing_logs'))->contains(fn ($fk) => $fk['columns'] === ['organization_id'])) {
                Schema::table('post_publishing_logs', function (Blueprint $table) {
                    $table->dropForeign(['organization_id']);
                });
            }
            Schema::table('post_publishing_logs', function (Blueprint $table) {
                $table->dropColumn('organization_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('post_publishing_logs', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        Schema::table('post_product_blocks', function (Blueprint $table) {
            $table->dropIndex('idx_post_pb_translation');
        });
        Schema::table('post_product_blocks', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('uuid')->constrained()->restrictOnDelete();
        });
        Schema::table('post_product_blocks', function (Blueprint $table) {
            $table->index(['organization_id', 'translation_id'], 'idx_post_pb_org_translation');
        });

        Schema::table('post_content_blocks', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        Schema::table('post_article_translations', function (Blueprint $table) {
            $table->dropUnique('uq_post_trans_locale_slug');
            $table->dropIndex('idx_post_trans_status_pub');
        });
        Schema::table('post_article_translations', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('article_id')->constrained()->restrictOnDelete();
        });
        Schema::table('post_article_translations', function (Blueprint $table) {
            $table->unique(['organization_id', 'locale', 'slug'], 'uq_post_trans_org_locale_slug');
            $table->index(['organization_id', 'locale', 'status', 'published_at'], 'idx_post_trans_org_status_pub');
        });
    }
};
