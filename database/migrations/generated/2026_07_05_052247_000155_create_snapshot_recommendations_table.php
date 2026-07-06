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
        if (Schema::hasTable('snapshot_recommendations')) {
            return;
        }

        Schema::create('snapshot_recommendations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('snapshot_id')->constrained('assessment_config_snapshots')->cascadeOnDelete();
            $table->string('recommendation_code', 100);
            $table->string('label', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('trigger_domain', 60)->nullable();
            $table->decimal('threshold_score', 5, 2)->default(50);
            $table->unsignedSmallInteger('priority')->default(1);
            

            // Indexes
            $table->index('snapshot_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshot_recommendations');
    }
};