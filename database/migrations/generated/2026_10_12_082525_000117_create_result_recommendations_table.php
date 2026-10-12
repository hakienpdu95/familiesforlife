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
        if (Schema::hasTable('result_recommendations')) {
            return;
        }

        Schema::create('result_recommendations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('result_id')->constrained('assessment_results')->cascadeOnDelete();
            $table->string('recommendation_code', 100);
            $table->tinyInteger('priority')->default(1);
            $table->timestamps();
            

            // Indexes
            $table->unique(['result_id', 'recommendation_code']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('result_recommendations');
    }
};