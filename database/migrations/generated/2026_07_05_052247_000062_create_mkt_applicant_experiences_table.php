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
        if (Schema::hasTable('mkt_applicant_experiences')) {
            return;
        }

        Schema::create('mkt_applicant_experiences', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('applicant_id');
            $table->string('company_name', 200);
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->smallInteger('start_month');
            $table->smallInteger('start_year');
            $table->smallInteger('end_month')->nullable();
            $table->smallInteger('end_year')->nullable();
            $table->boolean('is_current')->default(false);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('mkt_applicant_experiences');
    }
};