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
        if (Schema::hasTable('job_titles')) {
            return;
        }

        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
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
            

            // Indexes
            $table->unique(['organization_id', 'code'], 'uq_job_title_code');
            $table->index(['organization_id', 'is_active'], 'idx_job_titles_org');
            $table->index(['organization_id', 'level'], 'idx_job_titles_level');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('job_titles');
    }
};