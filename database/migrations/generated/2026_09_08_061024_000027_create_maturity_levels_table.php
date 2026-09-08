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
        if (Schema::hasTable('maturity_levels')) {
            return;
        }

        Schema::create('maturity_levels', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('assessment_code', 50);
            $table->string('level_code', 50)->comment('e.g. DIGITAL_FOUNDATION');
            $table->string('label', 100)->comment('e.g. Nền tảng số cơ bản');
            $table->text('description')->nullable();
            $table->decimal('min_score', 5, 2)->comment('Overall score tối thiểu để đạt level này');
            $table->decimal('max_score', 5, 2)->comment('Overall score tối đa');
            $table->tinyInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->unique(['assessment_code', 'level_code']);
            $table->index('assessment_code');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('maturity_levels');
    }
};