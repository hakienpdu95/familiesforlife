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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'secondary_email')) {
                $table->string('secondary_email', 255)->nullable();
            }
            if (!Schema::hasColumn('customers', 'secondary_phone')) {
                $table->string('secondary_phone', 30)->nullable()->after('secondary_email');
            }
            if (!Schema::hasColumn('customers', 'province_name')) {
                $table->string('province_name', 100)->nullable()->after('secondary_phone');
            }
            if (!Schema::hasColumn('customers', 'ward_code')) {
                $table->string('ward_code', 20)->nullable()->after('province_name');
            }
            if (!Schema::hasColumn('customers', 'ward_name')) {
                $table->string('ward_name', 100)->nullable()->after('ward_code');
            }
            if (!Schema::hasColumn('customers', 'address_line')) {
                $table->string('address_line', 500)->nullable()->after('ward_name');
            }
            if (!Schema::hasColumn('customers', 'notes')) {
                $table->string('notes', 2000)->nullable()->after('address_line');
            }
            if (!Schema::hasColumn('customers', 'customer_code')) {
                $table->string('customer_code', 50)->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $cols = array_filter(['secondary_email', 'secondary_phone', 'province_name', 'ward_code', 'ward_name', 'address_line', 'notes', 'customer_code'], fn($c) => Schema::hasColumn('customers', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};