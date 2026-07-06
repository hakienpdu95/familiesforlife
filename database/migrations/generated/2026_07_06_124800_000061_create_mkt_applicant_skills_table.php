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
        if (Schema::hasTable('mkt_applicant_skills')) {
            return;
        }

        Schema::create('mkt_applicant_skills', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('applicant_id');
            $table->string('skill_name', 100);
            $table->string('proficiency_level', 20)->default('intermediate');
            $table->smallInteger('years_used')->nullable();
            $table->smallInteger('sort_order')->default(0);
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('mkt_applicant_skills');
    }
};