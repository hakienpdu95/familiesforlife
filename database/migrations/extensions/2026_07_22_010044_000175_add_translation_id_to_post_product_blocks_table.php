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
        Schema::table('post_product_blocks', function (Blueprint $table) {
            if (!Schema::hasColumn('post_product_blocks', 'translation_id')) {
                $table->unsignedBigInteger('translation_id')->nullable();
            }
            if (!Schema::hasIndex('post_product_blocks', 'idx_post_pb_translation')) {
                $table->index('translation_id', 'idx_post_pb_translation');
            }
            // idx_post_pb_org_translation (organization_id, translation_id) KHÔNG được tạo lại ở
            // đây — organization_id đã bị xoá khỏi post_product_blocks có chủ đích bởi migration
            // 2026_07_13_000002_drop_organization_id_from_post_child_tables.php (Post đã ra khỏi
            // phạm vi đa tenant, spec/Platform_RBAC_Phase2_Specification.md v3.0). Migration này
            // được auto-generate từ 1 snapshot CŨ hơn (trước khi cột bị xoá) nên còn sót lại thao
            // tác tạo index theo cột đã không còn tồn tại — bug tiền tồn, chặn mọi
            // RefreshDatabase migrate lại từ đầu (SQLSTATE 1072 "Key column 'organization_id'
            // doesn't exist"), không liên quan gì tới Related Posts Engine.
        });
    }

    public function down(): void
    {
        Schema::table('post_product_blocks', function (Blueprint $table) {
            $cols = array_filter(['translation_id'], fn($c) => Schema::hasColumn('post_product_blocks', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};