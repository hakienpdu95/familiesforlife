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
        if (Schema::hasTable('mkt_listings')) {
            return;
        }

        Schema::create('mkt_listings', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->char('jp_job_post_id', 36)->nullable()->index()->comment('Soft ref → jp_job_posts.uuid');
            $table->unsignedBigInteger('org_id')->nullable()->index();
            $table->string('poster_type', 20)->default('org')->comment('org | individual');
            $table->string('listing_type', 20)->default('job')->comment('job | internship');
            $table->string('title', 300);
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->decimal('salary_min', 15, 2)->nullable();
            $table->decimal('salary_max', 15, 2)->nullable();
            $table->string('salary_currency', 3)->default('VND');
            $table->string('employment_type', 30)->nullable();
            $table->string('work_type', 30)->nullable()->comment('work_arrangement value');
            $table->string('experience_level', 30)->nullable();
            $table->string('location', 200)->nullable()->comment('city + province concatenated');
            $table->smallInteger('headcount')->default(1);
            $table->string('status', 20)->default('active')->comment('draft|active|closed|paused');
            $table->timestamp('expire_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('application_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('mkt_listings');
    }
};