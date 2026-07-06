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
        Schema::table('deployment_progress_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('deployment_progress_logs', 'deployment_checklist_item_id')) {
                $table->foreignId('deployment_checklist_item_id')->nullable()->constrained('deployment_checklist_items')->nullOnDelete()->comment('Null nếu log ghi nhận chung (form Nhật ký tiến độ), có giá trị nếu log gắn với 1 mục checklist cụ thể (tick hoặc ghi chú riêng)');
            }
            if (!Schema::hasIndex('deployment_progress_logs', 'deployment_progress_logs_deployment_checklist_item_id_index')) {
                $table->index('deployment_checklist_item_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deployment_progress_logs', function (Blueprint $table) {
            if (Schema::hasColumn('deployment_progress_logs', 'deployment_checklist_item_id')) $table->dropForeign(['deployment_checklist_item_id']);
            $cols = array_filter(['deployment_checklist_item_id'], fn($c) => Schema::hasColumn('deployment_progress_logs', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};