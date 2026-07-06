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
        if (Schema::hasTable('jp_job_post_skills')) {
            return;
        }

        Schema::create('jp_job_post_skills', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('job_post_id')->constrained('jp_job_posts')->cascadeOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained('jp_skill_masters')->nullOnDelete();
            $table->string('skill_name', 100);
            $table->string('requirement_level', 15)->default('required');
            $table->string('proficiency', 15)->nullable();
            $table->smallInteger('min_years')->nullable();
            $table->smallInteger('sort_order')->default(0);
            

            // Indexes
            $table->index(['job_post_id', 'requirement_level'], 'idx_jp_skill_post');
            $table->index('skill_id', 'idx_jp_skill_ref');
            $table->index(['skill_name', 'requirement_level'], 'idx_jp_skill_name');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('jp_job_post_skills');
    }
};