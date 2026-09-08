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
        if (Schema::hasTable('assessments')) {
            return;
        }

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('assessment_code', 50)->unique()->comment('FK logic tới surveys.assessment_code');
            $table->string('name', 255);
            $table->string('version', 20)->default('1.0');
            $table->boolean('is_active')->default(true);
            $table->boolean('has_scoring')->default(false)->comment('FALSE → bỏ qua toàn bộ engine, chỉ lưu câu trả lời');
            $table->string('aggregation_model', 30)->default('flat_sum')->comment('flat_sum | weighted_domain | sectioned');
            $table->string('classification_type', 30)->default('none')->comment('none | score_band | pass_fail | persona_match');
            $table->timestamps();
            $table->softDeletes();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};