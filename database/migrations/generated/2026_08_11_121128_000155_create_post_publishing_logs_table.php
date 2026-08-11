<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('post_publishing_logs')) {
            return;
        }

        Schema::create('post_publishing_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('translation_id')->constrained('post_article_translations')->cascadeOnDelete();
            $table->string('action', 20);
            $table->string('reason', 500)->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            // Indexes
            $table->index(['translation_id', 'created_at'], 'idx_post_pub_log_translation');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('post_publishing_logs');
    }
};
