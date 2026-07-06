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
        if (Schema::hasTable('ocop_product_groups')) {
            return;
        }

        Schema::create('ocop_product_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('code', 60)->unique();
            $table->string('name', 255);
            $table->string('industry_code', 10);
            $table->string('industry_name', 255);
            $table->string('group_label', 255)->nullable();
            $table->string('managing_agency', 255)->nullable();
            $table->boolean('requires_sample_product')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['industry_code', 'sort_order']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_product_groups');
    }
};