<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('content_brief_versions')) {
            return;
        }

        Schema::create('content_brief_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('content_brief_id')->constrained('content_briefs')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('status', 20)->default('draft');
            $table->json('snapshot');
            $table->string('content_hash', 64);
            $table->string('trigger', 20);
            $table->foreignId('restored_from_version_id')->nullable()->constrained('content_brief_versions')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejected_reason', 500)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            // Indexes
            $table->unique(['content_brief_id', 'version_number'], 'uq_brief_version_number');
            $table->index(['content_brief_id', 'status'], 'idx_brief_version_status');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('content_brief_versions');
    }
};
