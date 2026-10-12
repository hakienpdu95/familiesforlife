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
        if (Schema::hasTable('snapshot_rules')) {
            return;
        }

        Schema::create('snapshot_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('snapshot_id')->constrained('assessment_config_snapshots')->cascadeOnDelete();
            $table->string('field_key', 100);
            $table->string('domain_code', 60)->nullable();
            $table->string('feature_code', 100)->nullable();
            $table->string('signal_flag', 100)->nullable();
            $table->integer('score_if_true')->default(0);
            $table->integer('score_if_false')->default(0);
            $table->string('question_scoring_type', 30)->default('none');
            $table->string('condition_type', 30)->default('none');
            $table->integer('min_score_cap')->nullable();
            $table->integer('max_score_cap')->nullable();
            

            // Indexes
            $table->index('snapshot_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshot_rules');
    }
};