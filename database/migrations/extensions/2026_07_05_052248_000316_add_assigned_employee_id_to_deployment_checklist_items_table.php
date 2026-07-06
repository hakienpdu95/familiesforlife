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
        Schema::table('deployment_checklist_items', function (Blueprint $table) {
            if (!Schema::hasColumn('deployment_checklist_items', 'assigned_employee_id')) {
                $table->foreignId('assigned_employee_id')->nullable()->constrained('employees')->nullOnDelete()->comment('Nhân viên được PM chỉ định phụ trách mục này — khác done_by (người thực tế đã tick)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deployment_checklist_items', function (Blueprint $table) {
            if (Schema::hasColumn('deployment_checklist_items', 'assigned_employee_id')) $table->dropForeign(['assigned_employee_id']);
            $cols = array_filter(['assigned_employee_id'], fn($c) => Schema::hasColumn('deployment_checklist_items', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};