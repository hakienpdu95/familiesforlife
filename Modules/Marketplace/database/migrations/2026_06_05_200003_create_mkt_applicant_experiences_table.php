<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mkt_applicant_experiences', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->nullable()->unique();
            $table->unsignedBigInteger('applicant_id');
            $table->foreign('applicant_id')->references('id')->on('mkt_applicants')->cascadeOnDelete();
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
