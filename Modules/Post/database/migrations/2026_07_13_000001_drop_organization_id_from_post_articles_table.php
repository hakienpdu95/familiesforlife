<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Platform_RBAC_Phase2_Specification.md §3.3 (v3.0) — Post là tài sản của nền tảng,
 * không thuộc về bất kỳ Organization nào. Form bài viết chưa từng có ô chọn tổ chức
 * (sponsor chỉ là text `sponsor_name`), nên giữ lại organization_id dưới bất kỳ hình thức
 * nào (kể cả rename/nullable) cũng sẽ là 1 cột chết không ai set được.
 *
 * idx_post_article_sponsored đổi thành (is_sponsored, sponsored_end_date) — khớp đúng
 * query thật của ExpireSponsoredArticlesJob (chưa bao giờ lọc theo organization_id trong
 * WHERE, chỉ dùng organization_id sau khi đã tìm ra bài để tra cứu tổ chức — nay bỏ hẳn).
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
        if (collect(Schema::getForeignKeys('post_articles'))->contains(fn ($fk) => $fk['columns'] === ['organization_id'])) {
            Schema::table('post_articles', function (Blueprint $table) {
                $table->dropForeign(['organization_id']);
            });
        }

        if (collect(Schema::getIndexes('post_articles'))->contains('name', 'idx_post_article_org_format')) {
            Schema::table('post_articles', function (Blueprint $table) {
                $table->dropIndex('idx_post_article_org_format');
            });
        }

        if (collect(Schema::getIndexes('post_articles'))->contains(fn ($idx) => $idx['name'] === 'idx_post_article_sponsored' && $idx['columns'] === ['organization_id', 'is_sponsored', 'sponsored_end_date'])) {
            Schema::table('post_articles', function (Blueprint $table) {
                $table->dropIndex('idx_post_article_sponsored');
            });
        }

        if (Schema::hasColumn('post_articles', 'organization_id')) {
            Schema::table('post_articles', function (Blueprint $table) {
                $table->dropColumn('organization_id');
            });
        }

        if (! collect(Schema::getIndexes('post_articles'))->contains('name', 'idx_post_article_sponsored')) {
            Schema::table('post_articles', function (Blueprint $table) {
                $table->index(['is_sponsored', 'sponsored_end_date'], 'idx_post_article_sponsored');
            });
        }
    }

    public function down(): void
    {
        Schema::table('post_articles', function (Blueprint $table) {
            $table->dropIndex('idx_post_article_sponsored');
        });

        Schema::table('post_articles', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('main_locale')->constrained()->restrictOnDelete();
        });

        Schema::table('post_articles', function (Blueprint $table) {
            $table->index(['organization_id', 'format'], 'idx_post_article_org_format');
            $table->index(['organization_id', 'is_sponsored', 'sponsored_end_date'], 'idx_post_article_sponsored');
        });
    }
};
