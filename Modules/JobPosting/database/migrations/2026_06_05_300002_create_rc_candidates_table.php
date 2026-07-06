<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Stub table — will be owned by Recruitment Center module when built.
// Created here so Phase 3 career page apply flow can write to it.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rc_candidates', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID');
            $table->unsignedBigInteger('org_id')->index();
            $table->string('full_name', 200);
            $table->string('email', 150)->index();
            $table->string('phone', 30)->nullable();
            $table->text('resume_url')->nullable()->comment('URL to uploaded CV/resume');
            $table->string('portfolio_url', 500)->nullable();
            $table->string('linkedin_url', 500)->nullable();
            $table->string('source', 50)->default('career_page')->comment('career_page|marketplace|referral|direct');
            $table->timestamps();

            $table->index(['org_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_candidates');
    }
};
