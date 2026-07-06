<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Stub table — will be owned by Marketplace Center module when built.
// Created here so Phase 3 Job Posting sync can write to it.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mkt_listings', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID');
            $table->char('jp_job_post_id', 36)->nullable()->index()->comment('Soft ref → jp_job_posts.uuid');
            $table->unsignedBigInteger('org_id')->index();
            $table->string('poster_type', 20)->default('org')->comment('org | individual');
            $table->string('listing_type', 20)->default('job')->comment('job | internship');
            $table->string('title', 200);
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
