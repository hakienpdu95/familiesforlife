<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roadmap_milestone_kc_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('roadmap_milestone_id');
            $table->unsignedBigInteger('kc_item_id');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['roadmap_milestone_id', 'kc_item_id'], 'rm_kc_unique');
            $table->foreign('roadmap_milestone_id')
                ->references('id')->on('roadmap_milestones')->cascadeOnDelete();
            $table->foreign('kc_item_id')
                ->references('id')->on('kc_items')->cascadeOnDelete();
            $table->index('kc_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roadmap_milestone_kc_items');
    }
};
