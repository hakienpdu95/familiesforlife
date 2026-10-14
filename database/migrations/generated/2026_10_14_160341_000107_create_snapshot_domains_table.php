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
        if (Schema::hasTable('snapshot_domains')) {
            return;
        }

        Schema::create('snapshot_domains', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('snapshot_id')->constrained('assessment_config_snapshots')->cascadeOnDelete();
            $table->string('domain_code', 60);
            $table->string('label', 120)->nullable();
            $table->decimal('weight', 8, 4)->default(0);
            $table->smallInteger('min_score')->default(0);
            $table->smallInteger('max_score')->default(100);
            $table->unsignedSmallInteger('sort_order')->default(0);
            

            // Indexes
            $table->index('snapshot_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshot_domains');
    }
};