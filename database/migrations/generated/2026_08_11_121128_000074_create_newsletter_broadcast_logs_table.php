<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('newsletter_broadcast_logs')) {
            return;
        }

        Schema::create('newsletter_broadcast_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('resend_broadcast_id', 36)->nullable();
            $table->string('subject', 255);
            $table->timestamp('scheduled_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            // Indexes
            $table->index('sent_by');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_broadcast_logs');
    }
};
