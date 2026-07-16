<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chuẩn hóa danh mục OCOP theo spec/danhmuc.html (bảng phân loại sản phẩm OCOP chính thức) — 3
 * cấp: Nhóm lớn (I–VI) → Nhóm → Phân nhóm, kèm "Cơ quan chủ trì quản lý" ở cấp sâu nhất của mỗi
 * nhánh. Cùng pattern parent_id/depth với Modules/Menu (MenuItem) — depth cache lại (không tính
 * đệ quy mỗi lần), enforce tối đa 3 cấp ở OcopCategorySeeder, không phải CHECK constraint DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ocop_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('ocop_categories', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('uuid')
                    ->constrained('ocop_categories')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('ocop_categories', 'depth')) {
                $table->unsignedTinyInteger('depth')->default(0)->after('parent_id')
                    ->comment('0=nhóm lớn (I-VI), 1=nhóm, 2=phân nhóm — cache lại, không tính đệ quy');
            }
            if (! Schema::hasColumn('ocop_categories', 'code')) {
                $table->string('code', 10)->nullable()->after('slug')
                    ->comment('STT chính thức theo spec/danhmuc.html — vd I, 1, a');
            }
            if (! Schema::hasColumn('ocop_categories', 'authority')) {
                $table->string('authority', 255)->nullable()->after('icon')
                    ->comment('Cơ quan chủ trì quản lý — chỉ có ở cấp sâu nhất của mỗi nhánh');
            }
        });

        Schema::table('ocop_categories', function (Blueprint $table) {
            $table->index(['parent_id', 'sort_order'], 'idx_ocop_category_tree');
            $table->index(['depth', 'sort_order'], 'idx_ocop_category_depth');
        });
    }

    public function down(): void
    {
        Schema::table('ocop_categories', function (Blueprint $table) {
            $table->dropIndex('idx_ocop_category_tree');
            $table->dropIndex('idx_ocop_category_depth');
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['depth', 'code', 'authority']);
        });
    }
};
