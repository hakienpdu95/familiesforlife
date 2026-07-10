<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aicem_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_run_id')->constrained('aicem_generation_runs')->cascadeOnDelete();

            // organization_id: giữ để truy vết trực tiếp an toàn hơn khi audit — model KHÔNG extends
            // TenantAwareModel (không cần global scope riêng, luôn truy cập qua generation_run), mục 7.
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();

            // field NULL + block_id set  → suggestion cho 1 PostContentBlock (chỉ post_article).
            // field set + block_id NULL  → suggestion cho 1 field rời (post_article hoặc product).
            // Ràng buộc field/block hợp lệ do registry enforce ở ValidateSuggestionsAction (mục 6.9.2),
            // không encode cứng trong migration.
            $table->string('field', 100)->nullable();
            $table->unsignedBigInteger('block_id')->nullable();

            // original_text = giá trị THẬT của field/block lúc generate (đọc từ resolver, không tin
            // model) — mốc phát hiện staleness ở mục 9.1.
            $table->longText('original_text');
            $table->longText('suggested_text');
            $table->text('reason')->nullable();

            $table->string('status', 20)->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();

            $table->timestamps();

            $table->index(['generation_run_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aicem_suggestions');
    }
};
