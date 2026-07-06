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
        if (Schema::hasTable('ocop_products')) {
            return;
        }

        Schema::create('ocop_products', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_group_id')->constrained('ocop_product_groups')->restrictOnDelete();
            $table->string('name', 255);
            $table->string('product_code', 60)->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('best_practice_score', 5, 2)->nullable();
            $table->unsignedTinyInteger('best_practice_star_rank')->nullable();
            $table->decimal('latest_self_assessment_score', 5, 2)->nullable();
            $table->unsignedTinyInteger('latest_self_assessment_star_rank')->nullable();
            $table->unsignedBigInteger('latest_self_assessment_session_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['organization_id', 'product_group_id']);
            $table->index(['organization_id', 'status']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_products');
    }
};