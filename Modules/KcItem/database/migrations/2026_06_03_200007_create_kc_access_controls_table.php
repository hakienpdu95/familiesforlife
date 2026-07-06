<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kc_access_controls', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('item_id')->constrained('kc_items')->cascadeOnDelete();
            $table->enum('target_type', ['user', 'role', 'dept'])->index();
            $table->unsignedBigInteger('target_id')->index();
            $table->enum('permission', ['view', 'edit', 'manage'])->default('view');
            $table->timestamp('granted_at')->useCurrent();
            $table->foreignId('granted_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('expired_at')->nullable();

            $table->unique(['item_id', 'target_type', 'target_id'], 'uq_kc_access');
            $table->index(['target_type', 'target_id'], 'idx_kc_access_target');
            $table->index('item_id', 'idx_kc_access_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kc_access_controls');
    }
};
