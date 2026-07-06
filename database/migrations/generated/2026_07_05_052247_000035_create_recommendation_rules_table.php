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
        if (Schema::hasTable('recommendation_rules')) {
            return;
        }

        Schema::create('recommendation_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('assessment_code', 50);
            $table->string('recommendation_code', 100)->comment('e.g. crm_setup');
            $table->string('label', 255);
            $table->text('description')->nullable();
            $table->string('trigger_domain', 50)->comment('Domain code trigger rule này');
            $table->decimal('threshold_score', 5, 2)->comment('Trigger khi normalized_domain_score < threshold');
            $table->tinyInteger('priority')->default(1)->comment('1 = cao nhất');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            

            // Indexes
            $table->unique(['assessment_code', 'recommendation_code']);
            $table->index('assessment_code');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_rules');
    }
};