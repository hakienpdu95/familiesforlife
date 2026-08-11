<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aicem_knowledge_document_versions')) {
            return;
        }

        Schema::create('aicem_knowledge_document_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('knowledge_document_id')->constrained('aicem_knowledge_documents')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('content');
            $table->json('scope')->nullable();
            $table->string('scope_match', 10)->default('any');
            $table->integer('priority')->default(100);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->useCurrent();

            // Indexes
            $table->unique(['knowledge_document_id', 'version'], 'aicem_kdv_doc_version_unique');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('aicem_knowledge_document_versions');
    }
};
