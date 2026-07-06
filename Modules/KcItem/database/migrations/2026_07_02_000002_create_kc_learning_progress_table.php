<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kc_learning_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('kc_item_id');
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'kc_item_id'], 'klp_user_kc_unique');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('kc_item_id')->references('id')->on('kc_items')->cascadeOnDelete();
            $table->index('kc_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kc_learning_progress');
    }
};
