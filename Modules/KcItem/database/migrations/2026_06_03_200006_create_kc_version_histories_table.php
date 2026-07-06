<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kc_version_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('item_id')->constrained('kc_items')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('title_snapshot', 300);
            $table->longText('content_snapshot');
            $table->text('change_summary')->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('changed_at')->useCurrent();

            $table->unique(['item_id', 'version_number'], 'uq_kc_ver_item_version');
            $table->index('item_id', 'idx_kc_ver_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kc_version_histories');
    }
};
