<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('category', 30)->default('staff');
            $table->unsignedTinyInteger('level')->default(1);
            $table->text('description')->nullable();
            $table->tinyInteger('is_system')->default(0);
            $table->tinyInteger('is_locked')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code'], 'uq_job_title_code');
            $table->index(['organization_id', 'is_active'], 'idx_job_titles_org');
            $table->index(['organization_id', 'level'], 'idx_job_titles_level');
        });

        DB::statement("ALTER TABLE job_titles ADD CONSTRAINT chk_job_title_level CHECK (level BETWEEN 1 AND 20)");
        DB::statement("ALTER TABLE job_titles ADD CONSTRAINT chk_job_title_category CHECK (category IN ('executive','manager','supervisor','staff','intern','consultant'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('job_titles');
    }
};
