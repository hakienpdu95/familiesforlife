<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trường hợp 3 (docs/migration-guide.md) — DROP index không biểu diễn được qua
 * render_migration_file.json / render_extension_file.json (action add/drop chỉ hỗ trợ
 * cột + thêm index, không có action xoá index). Toàn bộ phần ADD (cột + index mới,
 * kể cả uq_vertical_template_org_code / uq_vertical_config_item_template /
 * idx_vertical_config_lookup_template) đã khai báo qua render_extension_file.json —
 * migration này chỉ xử lý phần DROP không thể biểu diễn được, theo
 * docs/refactor-vertical-deployment.md §2.2.
 */
return new class extends Migration
{
    public function up(): void
    {
        // vertical_templates: bỏ unique global trên code (thay bằng uq_vertical_template_org_code,
        // đã tạo qua render_extension_file.json)
        Schema::table('vertical_templates', function (Blueprint $table) {
            if (Schema::hasIndex('vertical_templates', ['code'])) {
                $table->dropUnique(['code']);
            }
        });

        // vertical_config_items: sau khi organization_id/vertical_code bị drop (extension migration),
        // MySQL tự rút gọn 2 index cũ còn (config_group, code) / (config_group, is_active) thay vì xoá
        // hẳn — phải xoá tường minh, nếu không unique constraint sai phạm vi sẽ chặn nhầm các config
        // item trùng code giữa các vertical_template khác nhau. Index đúng phạm vi (theo
        // vertical_template_id) đã được thêm qua render_extension_file.json với tên khác
        // (uq_vertical_config_item_template / idx_vertical_config_lookup_template).
        Schema::table('vertical_config_items', function (Blueprint $table) {
            if (Schema::hasIndex('vertical_config_items', 'uq_vertical_config_item')) {
                $table->dropUnique('uq_vertical_config_item');
            }
            if (Schema::hasIndex('vertical_config_items', 'idx_vertical_config_lookup')) {
                $table->dropIndex('idx_vertical_config_lookup');
            }
        });

        // organization_verticals: vai trò hấp thụ vào cột mới trên vertical_templates
        // (organization_id, status, activated_at, activated_by) — bảng cũ không còn cần thiết.
        // Xoá bảng không biểu diễn được qua JSON manifest (chỉ tạo/ALTER bảng, không có action drop table).
        Schema::dropIfExists('organization_verticals');
    }

    public function down(): void
    {
        // Không rollback — cleanup 1 chiều của refactor vertical/deployment
        // (docs/refactor-vertical-deployment.md). Khôi phục cần revert JSON manifest cũ
        // + chạy lại migration:generate --fresh trên local, hoặc backup DB trước khi migrate ở prod.
    }
};
