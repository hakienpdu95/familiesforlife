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
        if (Schema::hasTable('assessment_results')) {
            return;
        }

        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('subject_type', 150)->comment('FQCN của model subject');
            $table->unsignedBigInteger('subject_id')->comment('PK của subject model');
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->string('maturity_level', 64)->nullable()->comment('band_code hoặc persona_code');
            $table->string('assessment_code', 64);
            $table->unsignedSmallInteger('weight_version')->default(1);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['subject_type', 'subject_id'], 'uq_ar_subject');
            $table->index('assessment_code', 'idx_ar_code');
            $table->index(['assessment_code', 'maturity_level'], 'idx_ar_code_band');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_results');
    }
};