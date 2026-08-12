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
        if (Schema::hasTable('score_bands')) {
            return;
        }

        Schema::create('score_bands', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('assessment_code', 50)->comment('FK logic tới assessments.assessment_code');
            $table->string('band_code', 50)->comment('e.g. MANUAL_OPERATION, AI_READY');
            $table->string('label', 100);
            $table->text('description')->nullable();
            $table->decimal('min_score', 5, 2)->comment('Ngưỡng hiện hành (dưới, inclusive)');
            $table->decimal('max_score', 5, 2)->comment('Ngưỡng hiện hành (trên, inclusive)');
            $table->decimal('default_min', 5, 2)->comment('Ngưỡng gốc (để reset)');
            $table->decimal('default_max', 5, 2)->comment('Ngưỡng gốc (để reset)');
            $table->boolean('is_dynamic')->default(false)->comment('TRUE = ngưỡng có thể điều chỉnh tự động');
            $table->string('lead_temperature', 10)->default('cold')->comment('hot | warm | cold — mirror với maturity_levels');
            $table->tinyInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->unique(['assessment_code', 'band_code'], 'uq_score_band');
            $table->index('assessment_code');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('score_bands');
    }
};