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
        if (Schema::hasTable('passport_impact_highlights')) {
            return;
        }

        Schema::create('passport_impact_highlights', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('passport_entry_id')->constrained('passport_entries')->cascadeOnDelete();
            $table->unsignedBigInteger('source_impact_id')->nullable()->comment('FK ai_impact_snapshots.id — nullable nếu record gốc bị xóa');
            $table->string('title', 300);
            $table->string('impact_category', 30)->comment('learning|productivity|quality|ai_adoption|business');
            $table->string('impact_type', 80)->nullable();
            $table->decimal('baseline_value', 12, 4)->nullable();
            $table->decimal('achieved_value', 12, 4)->nullable();
            $table->decimal('improvement_pct', 7, 2)->nullable();
            $table->decimal('roi_pct', 7, 2)->nullable();
            $table->string('period_label', 50)->nullable()->comment('VD: \"Tháng 3/2026\"');
            $table->unsignedTinyInteger('sort_order')->default(0);
            

            // Indexes
            $table->index('passport_entry_id', 'pih_entry_index');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_impact_highlights');
    }
};