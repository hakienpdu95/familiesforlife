<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aicem_knowledge_documents')) {
            return;
        }

        Schema::create('aicem_knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('subject_type', 50)->nullable();
            $table->json('scope')->nullable();
            $table->string('scope_match', 10)->default('any');
            $table->integer('priority')->default(100);
            $table->string('title', 255);
            $table->longText('content');
            $table->unsignedInteger('current_version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['organization_id', 'type']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('aicem_knowledge_documents');
    }
};
