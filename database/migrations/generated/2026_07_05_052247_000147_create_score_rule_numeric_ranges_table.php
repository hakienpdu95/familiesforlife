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
        if (Schema::hasTable('score_rule_numeric_ranges')) {
            return;
        }

        Schema::create('score_rule_numeric_ranges', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('rule_id')->constrained('score_rules')->cascadeOnDelete();
            $table->decimal('min_value', 10, 2)->nullable()->comment('NULL = không giới hạn dưới');
            $table->decimal('max_value', 10, 2)->nullable()->comment('NULL = không giới hạn trên');
            $table->integer('score');
            $table->string('signal_flag', 100)->nullable();
            $table->tinyInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->index('rule_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('score_rule_numeric_ranges');
    }
};