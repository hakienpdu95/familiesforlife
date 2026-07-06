<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mkt_applicant_portfolios', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->nullable()->unique();
            $table->unsignedBigInteger('applicant_id');
            $table->foreign('applicant_id')->references('id')->on('mkt_applicants')->cascadeOnDelete();
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
