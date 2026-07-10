<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aicem_knowledge_document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_document_id')->constrained('aicem_knowledge_documents')->cascadeOnDelete();

            // organization_id: giữ để truy vết trực tiếp an toàn hơn khi audit — model KHÔNG extends
            // TenantAwareModel (không cần global scope riêng, luôn truy cập qua model cha), xem mục 7.
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();

            $table->unsignedInteger('version');
            $table->longText('content');

            // Snapshot cả scope lúc đó — đổi điều kiện áp dụng cũng là 1 thay đổi cần rollback được.
            $table->json('scope')->nullable();
            $table->string('scope_match', 10)->default('any');
            $table->integer('priority')->default(100);

            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->useCurrent();

            $table->unique(['knowledge_document_id', 'version'], 'aicem_kdv_doc_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aicem_knowledge_document_versions');
    }
};
