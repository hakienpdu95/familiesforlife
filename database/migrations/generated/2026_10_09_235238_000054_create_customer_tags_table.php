<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_tags')) {
            return;
        }

        Schema::create('customer_tags', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id');
            $table->string('name', 100);
            $table->string('color', 20)->default('#6b7280');
            $table->timestamps();
            

            // Indexes
            $table->unique(['organization_id', 'name'], 'uq_ctag_org_name');
            $table->index('organization_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_tags');
    }
};