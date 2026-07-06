<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kc_item_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('item_id')->constrained('kc_items')->cascadeOnDelete();
            $table->string('file_name', 255);
            $table->text('file_url');
            $table->string('file_type', 50);
            $table->unsignedInteger('file_size_kb');
            $table->string('storage_provider', 20)->default('local');
            $table->string('storage_key', 500);
            $table->integer('sort_order')->default(0);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('uploaded_at')->useCurrent();

            $table->index(['item_id', 'sort_order'], 'idx_kc_attach_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kc_item_attachments');
    }
};
