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
        if (Schema::hasTable('mkt_applicants')) {
            return;
        }

        Schema::create('mkt_applicants', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('email', 150)->unique();
            $table->string('password_hash', 255);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('account_type', 20)->default('individual');
            $table->string('display_name', 150);
            $table->string('slug', 160)->unique();
            $table->string('headline', 200)->nullable();
            $table->text('bio')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('location', 150)->nullable();
            $table->text('avatar_url')->nullable();
            $table->string('website_url', 300)->nullable();
            $table->string('linkedin_url', 300)->nullable();
            $table->smallInteger('years_experience')->nullable();
            $table->decimal('expected_salary_min', 15, 2)->nullable();
            $table->decimal('expected_salary_max', 15, 2)->nullable();
            $table->char('salary_currency', 3)->default('VND');
            $table->string('status', 20)->default('active');
            $table->string('availability', 20)->default('negotiable');
            $table->boolean('is_profile_public')->default(true);
            $table->boolean('is_email_public')->default(false);
            $table->smallInteger('profile_complete_pct')->default(0);
            $table->integer('total_applications')->default(0);
            $table->integer('hired_count')->default(0);
            $table->decimal('avg_rating', 3, 2)->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('mkt_applicants');
    }
};