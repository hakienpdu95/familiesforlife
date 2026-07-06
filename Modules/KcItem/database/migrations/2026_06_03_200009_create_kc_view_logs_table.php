<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kc_view_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('item_id')->constrained('kc_items')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('viewed_at')->useCurrent()->index();

            $table->index(['item_id', 'viewed_at'], 'idx_kc_viewlog_item');
            $table->index(['user_id', 'viewed_at'], 'idx_kc_viewlog_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kc_view_logs');
    }
};
