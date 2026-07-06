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
        if (Schema::hasTable('kc_version_histories')) {
            return;
        }

        Schema::create('kc_version_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('item_id')->constrained('kc_items')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('title_snapshot', 300);
            $table->longText('content_snapshot');
            $table->text('change_summary')->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('changed_at')->useCurrent();
            

            // Indexes
            $table->unique(['item_id', 'version_number'], 'uq_kc_ver_item_version');
            $table->index('item_id', 'idx_kc_ver_item');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('kc_version_histories');
    }
};