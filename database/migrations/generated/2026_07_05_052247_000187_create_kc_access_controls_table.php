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
        if (Schema::hasTable('kc_access_controls')) {
            return;
        }

        Schema::create('kc_access_controls', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('item_id')->constrained('kc_items')->cascadeOnDelete();
            $table->enum('target_type', ['user', 'role', 'dept'])->index();
            $table->unsignedBigInteger('target_id')->index();
            $table->enum('permission', ['view', 'edit', 'manage'])->default('view');
            $table->timestamp('granted_at')->useCurrent();
            $table->foreignId('granted_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('expired_at')->nullable();
            

            // Indexes
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