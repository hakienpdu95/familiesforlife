<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kc_feedbacks')) {
            return;
        }

        Schema::create('kc_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('item_id')->constrained('kc_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->smallInteger('rating')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_helpful')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['item_id', 'user_id'], 'uq_kc_feedback_user');
            $table->index('item_id', 'idx_kc_feedback_item');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('kc_feedbacks');
    }
};