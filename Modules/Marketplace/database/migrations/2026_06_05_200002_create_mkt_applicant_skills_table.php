<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mkt_applicant_skills', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->nullable()->unique();
            $table->unsignedBigInteger('applicant_id');
            $table->foreign('applicant_id')->references('id')->on('mkt_applicants')->cascadeOnDelete();
            $table->string('skill_name', 100);
            $table->string('proficiency_level', 20)->default('intermediate');
            $table->smallInteger('years_used')->nullable();
            $table->smallInteger('sort_order')->default(0);
        });

        DB::statement('CREATE UNIQUE INDEX idx_mkt_skill_unique ON mkt_applicant_skills(applicant_id, skill_name)');
        DB::statement('CREATE INDEX idx_mkt_skill_name ON mkt_applicant_skills(skill_name, proficiency_level)');
    }

    public function down(): void
    {
        Schema::dropIfExists('mkt_applicant_skills');
    }
};
