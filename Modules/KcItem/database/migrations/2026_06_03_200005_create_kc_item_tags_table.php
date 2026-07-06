<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kc_item_tags', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('item_id')->constrained('kc_items')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('kc_tags')->cascadeOnDelete();

            $table->unique(['item_id', 'tag_id'], 'uq_kc_item_tag');
            $table->index('tag_id', 'idx_kc_item_tag_tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kc_item_tags');
    }
};
