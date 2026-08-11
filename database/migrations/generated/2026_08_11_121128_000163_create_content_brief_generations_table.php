<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('content_brief_generations')) {
            return;
        }

        Schema::create('content_brief_generations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('content_brief_version_id')->constrained('content_brief_versions')->restrictOnDelete();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('pending');
            $table->json('output')->nullable();
            $table->string('error_message', 500)->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            // Indexes
            $table->index('content_brief_version_id', 'idx_brief_gen_version');
            $table->index('status', 'idx_brief_gen_status');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('content_brief_generations');
    }
};
