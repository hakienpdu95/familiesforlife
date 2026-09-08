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
        if (Schema::hasTable('result_classifications')) {
            return;
        }

        Schema::create('result_classifications', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('result_id')->constrained('assessment_results')->cascadeOnDelete();
            $table->string('classification_type', 30)->comment('score_band | pass_fail | persona_match | none');
            $table->string('band_code', 50)->nullable()->comment('Dùng khi classification_type = score_band');
            $table->boolean('passed')->nullable()->comment('Dùng khi classification_type = pass_fail');
            $table->string('persona_code', 100)->nullable()->comment('Dùng khi classification_type = persona_match');
            $table->decimal('match_score', 5, 2)->nullable()->comment('Tỉ lệ điều kiện thỏa / tổng điều kiện (persona_match)');
            $table->timestamps();
            

            // Indexes
            $table->index(['result_id', 'band_code']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('result_classifications');
    }
};