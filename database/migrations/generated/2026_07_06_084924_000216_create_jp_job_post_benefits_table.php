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
        if (Schema::hasTable('jp_job_post_benefits')) {
            return;
        }

        Schema::create('jp_job_post_benefits', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('job_post_id')->constrained('jp_job_posts')->cascadeOnDelete();
            $table->foreignId('benefit_id')->nullable()->constrained('jp_benefit_masters')->nullOnDelete();
            $table->string('benefit_name', 150);
            $table->string('description', 300)->nullable();
            $table->smallInteger('sort_order')->default(0);
            

            // Indexes
            $table->index('job_post_id', 'idx_jp_benefit_post');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('jp_job_post_benefits');
    }
};