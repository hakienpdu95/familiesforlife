<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/PostTag_Management_Technical_Specification.md §3.5 — PostTag thuộc nền tảng vận hành,
 * không chịu quản lý theo tổ chức, phải chuyển platform-wide giống PostArticle/PostCategory.
 *
 * Migration ALTER thật (khác cách sửa thẳng migration tạo bảng gốc mà spec §3.5/§8 mục 7 đề
 * xuất ban đầu) — phát hiện khi implement: DB dev thật đã có 49 tag thật (tạo qua textbox viết
 * bài, không phải 0 như lúc khảo sát viết spec §2), nên KHÔNG thể chỉ sửa migration tạo bảng gốc
 * rồi `migrate:fresh` (sẽ xoá sạch dữ liệu thật của toàn bộ DB, không chỉ post_tags). Dùng đúng
 * pattern "Trường hợp 3" (guard hasColumn/getForeignKeys, xem
 * 2026_07_13_000002_drop_organization_id_from_post_child_tables.php) để giữ nguyên 49 tag đã có.
 *
 * Trước khi áp dụng đã verify: 0 slug trùng nhau giữa các tổ chức trong 49 tag hiện có, nên đổi
 * unique constraint từ (organization_id, slug) sang global (slug) không vi phạm dữ liệu cũ.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop FK trước — MySQL từ chối drop index khi nó còn hỗ trợ FK constraint. Index hỗ
        // trợ FK ở đây chính là uq_post_tag_org_slug (organization_id là cột đầu tiên).
        if (collect(Schema::getForeignKeys('post_tags'))->contains(fn ($fk) => $fk['columns'] === ['organization_id'])) {
            Schema::table('post_tags', function (Blueprint $table) {
                $table->dropForeign(['organization_id']);
            });
        }

        if (collect(Schema::getIndexes('post_tags'))->contains('name', 'uq_post_tag_org_slug')) {
            Schema::table('post_tags', function (Blueprint $table) {
                $table->dropUnique('uq_post_tag_org_slug');
            });
        }

        if (Schema::hasColumn('post_tags', 'organization_id')) {
            Schema::table('post_tags', function (Blueprint $table) {
                $table->dropColumn('organization_id');
            });
        }

        if (! collect(Schema::getIndexes('post_tags'))->contains('name', 'uq_post_tag_slug')) {
            Schema::table('post_tags', function (Blueprint $table) {
                $table->unique('slug', 'uq_post_tag_slug');
            });
        }
    }

    public function down(): void
    {
        if (collect(Schema::getIndexes('post_tags'))->contains('name', 'uq_post_tag_slug')) {
            Schema::table('post_tags', function (Blueprint $table) {
                $table->dropUnique('uq_post_tag_slug');
            });
        }

        Schema::table('post_tags', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        if (! collect(Schema::getIndexes('post_tags'))->contains('name', 'uq_post_tag_org_slug')) {
            Schema::table('post_tags', function (Blueprint $table) {
                $table->unique(['organization_id', 'slug'], 'uq_post_tag_org_slug');
            });
        }
    }
};
