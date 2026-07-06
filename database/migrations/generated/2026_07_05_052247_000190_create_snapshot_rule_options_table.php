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
        if (Schema::hasTable('snapshot_rule_options')) {
            return;
        }

        Schema::create('snapshot_rule_options', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('snapshot_rule_id')->constrained('snapshot_rules')->cascadeOnDelete();
            $table->string('option_value', 255);
            $table->string('option_label', 255)->nullable();
            $table->integer('score')->default(0);
            $table->string('signal_flag', 100)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            

            // Indexes
            $table->index('snapshot_rule_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshot_rule_options');
    }
};