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
        if (Schema::hasTable('roadmap_milestones')) {
            return;
        }

        Schema::create('roadmap_milestones', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('phase_id')->constrained('roadmap_phases')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->tinyInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->index('phase_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('roadmap_milestones');
    }
};