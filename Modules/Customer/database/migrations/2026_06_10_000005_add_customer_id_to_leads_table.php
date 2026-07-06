<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('contact_id');
            $table->index(['organization_id', 'customer_id'], 'idx_lead_customer');
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropIndex('idx_lead_customer');
            $table->dropColumn('customer_id');
        });
    }
};
