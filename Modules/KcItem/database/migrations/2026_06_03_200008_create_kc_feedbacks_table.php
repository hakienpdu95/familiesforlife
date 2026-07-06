<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kc_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('item_id')->constrained('kc_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->smallInteger('rating')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_helpful')->nullable();
            $table->timestamps();

            $table->unique(['item_id', 'user_id'], 'uq_kc_feedback_user');
            $table->index('item_id', 'idx_kc_feedback_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kc_feedbacks');
    }
};
