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
        if (Schema::hasTable('ocop_rubric_versions')) {
            return;
        }

        Schema::create('ocop_rubric_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('product_group_id')->constrained('ocop_product_groups')->restrictOnDelete();
            $table->unsignedSmallInteger('version_no');
            $table->string('status', 20)->default('draft');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('source_reference', 255)->default('QĐ 26/2026/QĐ-TTg, Phụ lục II');
            $table->decimal('total_max_score', 5, 2)->default(100.00);
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['product_group_id', 'version_no']);
            $table->index(['product_group_id', 'status']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_rubric_versions');
    }
};