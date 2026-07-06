<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('secondary_email', 255)->nullable()->after('primary_email');
            $table->string('secondary_phone', 30)->nullable()->after('primary_phone');
            $table->string('province_name', 100)->nullable()->after('province_code');
            $table->string('ward_code', 20)->nullable()->after('province_name');
            $table->string('ward_name', 100)->nullable()->after('ward_code');
            $table->string('address_line', 500)->nullable()->after('ward_name');
            $table->string('notes', 2000)->nullable()->after('description');
            $table->string('customer_code', 50)->nullable()->after('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'secondary_email', 'secondary_phone',
                'province_name', 'ward_code', 'ward_name', 'address_line',
                'notes', 'customer_code',
            ]);
        });
    }
};
