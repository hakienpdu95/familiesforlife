<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aicem_generation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();

            $table->string('subject_type', 50);
            // subject_id KHÔNG khai FK cứng tới post_articles/products (polymorphic tự quản qua
            // config/aicem_subjects.php, không dùng morphTo()) — tránh phải migrate lại nếu thêm
            // module chỉ định thứ 3 (mục 7).
            $table->unsignedBigInteger('subject_id');

            $table->foreignId('workflow_id')->constrained('aicem_workflows')->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();

            $table->string('provider', 30);
            $table->string('model', 100);
            $table->string('status', 20)->default('pending');

            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            // estimated_cost_usd: trần ước lượng reserve TRƯỚC khi gọi AI (mục 13.1);
            // cost_usd: chi phí thật SAU khi có token — reconcile trừ estimated, cộng cost_usd.
            $table->decimal('estimated_cost_usd', 10, 6)->nullable();
            $table->decimal('cost_usd', 10, 6)->nullable();

            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Tra lịch sử AI của 1 bài viết hoặc 1 sản phẩm cụ thể.
            $table->index(['organization_id', 'subject_type', 'subject_id'], 'aicem_gr_org_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aicem_generation_runs');
    }
};
