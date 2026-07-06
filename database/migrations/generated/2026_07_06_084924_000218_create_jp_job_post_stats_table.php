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
        if (Schema::hasTable('jp_job_post_stats')) {
            return;
        }

        Schema::create('jp_job_post_stats', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('job_post_id')->constrained('jp_job_posts')->cascadeOnDelete();
            $table->date('stat_date')->comment('Ngày thống kê');
            $table->string('source', 30)->default('direct')->comment('direct|marketplace|career_page|linkedin|referral|other');
            $table->integer('view_count')->default(0);
            $table->integer('unique_view_count')->default(0);
            $table->integer('apply_count')->default(0);
            $table->integer('share_count')->default(0);
            $table->integer('bookmark_count')->default(0);
            

            // Indexes
            $table->unique(['job_post_id', 'stat_date', 'source'], 'idx_jp_stat_grain');
            $table->index(['stat_date', 'job_post_id'], 'idx_jp_stat_date');
            $table->index(['job_post_id', 'stat_date'], 'idx_jp_stat_agg');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('jp_job_post_stats');
    }
};