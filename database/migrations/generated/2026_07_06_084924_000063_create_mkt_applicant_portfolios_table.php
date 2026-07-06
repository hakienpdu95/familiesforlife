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
        if (Schema::hasTable('mkt_applicant_portfolios')) {
            return;
        }

        Schema::create('mkt_applicant_portfolios', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('applicant_id');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('project_url', 300)->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->string('tech_stack', 300)->nullable();
            $table->smallInteger('completed_year')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('mkt_applicant_portfolios');
    }
};