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
        if (Schema::hasTable('rc_candidates')) {
            return;
        }

        Schema::create('rc_candidates', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('org_id')->index();
            $table->string('full_name', 200);
            $table->string('email', 150)->index();
            $table->string('phone', 30)->nullable();
            $table->text('resume_url')->nullable()->comment('URL to uploaded CV/resume');
            $table->string('portfolio_url', 500)->nullable();
            $table->string('linkedin_url', 500)->nullable();
            $table->string('source', 50)->default('career_page')->comment('career_page|marketplace|referral|direct');
            $table->timestamps();
            

            // Indexes
            $table->index(['org_id', 'email']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_candidates');
    }
};