<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocop_rubric_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_group_id')->constrained('ocop_product_groups')->restrictOnDelete();
            $table->unsignedSmallInteger('version_no');
            $table->string('status', 20)->default('draft');          // RubricVersionStatus
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('source_reference', 255)->default('QĐ 26/2026/QĐ-TTg, Phụ lục II');
            $table->decimal('total_max_score', 5, 2)->default(100.00);
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['product_group_id', 'version_no']);
            $table->index(['product_group_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_rubric_versions');
    }
};
