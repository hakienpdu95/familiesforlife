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
        Schema::table('subscription_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_invoices', 'gateway')) {
                $table->string('gateway', 32)->nullable();
            }
            if (!Schema::hasColumn('subscription_invoices', 'invoice_type')) {
                $table->string('invoice_type', 32)->default('renewal')->after('gateway');
            }
            if (!Schema::hasColumn('subscription_invoices', 'new_plan_id')) {
                $table->unsignedBigInteger('new_plan_id')->nullable()->after('invoice_type');
            }
            if (!Schema::hasIndex('subscription_invoices', 'idx_inv_new_plan')) {
                $table->index('new_plan_id', 'idx_inv_new_plan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $cols = array_filter(['gateway', 'invoice_type', 'new_plan_id'], fn($c) => Schema::hasColumn('subscription_invoices', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};