<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 12c (spec/PublishingEngine_Technical_Specification.md §3.3) — Migration #4.
 * Chỉ chạy sau khi `post:backfill-translations` (Phase 12b) đã xác nhận 100%
 * post_content_blocks/post_product_blocks có translation_id không null trên MỌI môi trường
 * (staging trước, production sau) — không dễ rollback (mất cột cũ vĩnh viễn).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Mỗi bước guard bằng hasColumn — cho phép resume an toàn nếu 1 statement giữa
        // chừng lỗi ở môi trường khác (MySQL DDL tự commit từng statement, không rollback
        // theo transaction bao ngoài như DML).
        //
        // Thứ tự bắt buộc cho 2 bảng con: DROP FOREIGN KEY + DROP COLUMN trước — 2 index cũ
        // idx_post_cb_article_order/idx_post_pb_org_article đang là index hỗ trợ cho chính FK
        // `article_id` này, MySQL từ chối DROP INDEX khi FK còn tồn tại ("needed in a foreign
        // key constraint"). Sau khi cột/FK bị xoá, index composite tự co lại (MySQL bỏ cột đã
        // xoá khỏi index nhiều cột), lúc đó mới drop được index cũ.
        //
        // dropForeign(['col'])+dropColumn('col') tách rời (không gộp dropConstrainedForeignId())
        // vì công cụ nội bộ php artisan migration:sync/extension:generate (đọc migration files
        // → JSON schema DSL) chỉ nhận diện dropColumn/dropForeign/dropUnique/dropIndex/dropPrimary
        // — dropConstrainedForeignId() không nằm trong danh sách, bị parser hiểu nhầm thành tên
        // kiểu cột "dropConstrainedForeignId" → GenerateExtension từ chối với lỗi "type không hợp lệ".
        if (Schema::hasColumn('post_content_blocks', 'article_id')) {
            Schema::table('post_content_blocks', function (Blueprint $table) {
                $table->foreignId('translation_id')->nullable(false)->change();
                $table->dropForeign(['article_id']);
                $table->dropColumn('article_id');
            });
        }
        if (collect(Schema::getIndexes('post_content_blocks'))->contains('name', 'idx_post_cb_article_order')) {
            Schema::table('post_content_blocks', function (Blueprint $table) {
                $table->dropIndex('idx_post_cb_article_order');
            });
        }

        if (Schema::hasColumn('post_product_blocks', 'article_id')) {
            Schema::table('post_product_blocks', function (Blueprint $table) {
                $table->foreignId('translation_id')->nullable(false)->change();
                $table->dropForeign(['article_id']);
                $table->dropColumn('article_id');
            });
        }
        if (collect(Schema::getIndexes('post_product_blocks'))->contains('name', 'idx_post_pb_org_article')) {
            Schema::table('post_product_blocks', function (Blueprint $table) {
                $table->dropIndex('idx_post_pb_org_article');
            });
        }

        if (collect(Schema::getIndexes('post_articles'))->contains('name', 'uq_post_article_org_slug')) {
            Schema::table('post_articles', function (Blueprint $table) {
                $table->dropUnique('uq_post_article_org_slug');
            });
        }
        if (collect(Schema::getIndexes('post_articles'))->contains('name', 'idx_post_article_org_status_pub')) {
            Schema::table('post_articles', function (Blueprint $table) {
                $table->dropIndex('idx_post_article_org_status_pub');
            });
        }
        if (Schema::hasColumn('post_articles', 'approved_by')) {
            Schema::table('post_articles', function (Blueprint $table) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            });
        }
        $remaining = array_intersect(
            ['title', 'slug', 'excerpt', 'status', 'published_at', 'seo_title', 'seo_description', 'approved_at', 'view_count'],
            Schema::getColumnListing('post_articles'),
        );
        if ($remaining !== []) {
            Schema::table('post_articles', function (Blueprint $table) use ($remaining) {
                $table->dropColumn($remaining);
            });
        }
    }

    public function down(): void
    {
        Schema::table('post_articles', function (Blueprint $table) {
            $table->string('title', 300)->default('');
            $table->string('slug', 320)->default('');
            $table->string('excerpt', 500)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('seo_title', 200)->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);

            $table->unique(['organization_id', 'slug'], 'uq_post_article_org_slug');
            $table->index(['organization_id', 'status', 'published_at'], 'idx_post_article_org_status_pub');
        });

        Schema::table('post_content_blocks', function (Blueprint $table) {
            $table->foreignId('article_id')->nullable()->constrained('post_articles')->cascadeOnDelete();
            $table->foreignId('translation_id')->nullable()->change();
            $table->index(['article_id', 'sort_order'], 'idx_post_cb_article_order');
        });

        Schema::table('post_product_blocks', function (Blueprint $table) {
            $table->foreignId('article_id')->nullable()->constrained('post_articles')->cascadeOnDelete();
            $table->foreignId('translation_id')->nullable()->change();
            $table->index(['organization_id', 'article_id'], 'idx_post_pb_org_article');
        });
    }
};
