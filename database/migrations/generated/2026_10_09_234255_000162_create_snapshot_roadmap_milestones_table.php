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
        if (Schema::hasTable('snapshot_roadmap_milestones')) {
            return;
        }

        Schema::create('snapshot_roadmap_milestones', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('snapshot_phase_id')->constrained('snapshot_roadmap_phases')->cascadeOnDelete();
            $table->string('title', 255);
            $table->unsignedSmallInteger('sort_order')->default(0);
            

            // Indexes
            $table->index('snapshot_phase_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshot_roadmap_milestones');
    }
};