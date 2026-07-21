<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ocop_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('ocop_categories', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->constrained('ocop_categories')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('ocop_categories', 'depth')) {
                $table->unsignedTinyInteger('depth')->default(0)->after('parent_id')->comment('0=nhóm lớn (I-VI), 1=nhóm, 2=phân nhóm — cache lại, không tính đệ quy');
            }
            if (!Schema::hasColumn('ocop_categories', 'code')) {
                $table->string('code', 10)->nullable()->after('depth')->comment('STT chính thức theo spec/danhmuc.html — vd I, 1, a');
            }
            if (!Schema::hasColumn('ocop_categories', 'authority')) {
                $table->string('authority', 255)->nullable()->after('code')->comment('Cơ quan chủ trì quản lý — chỉ có ở cấp sâu nhất của mỗi nhánh');
            }
            if (!Schema::hasIndex('ocop_categories', 'idx_ocop_category_tree')) {
                $table->index(['parent_id', 'sort_order'], 'idx_ocop_category_tree');
            }
            if (!Schema::hasIndex('ocop_categories', 'idx_ocop_category_depth')) {
                $table->index(['depth', 'sort_order'], 'idx_ocop_category_depth');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ocop_categories', function (Blueprint $table) {
            if (Schema::hasColumn('ocop_categories', 'parent_id')) $table->dropForeign(['parent_id']);
            $cols = array_filter(['parent_id', 'depth', 'code', 'authority'], fn($c) => Schema::hasColumn('ocop_categories', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};