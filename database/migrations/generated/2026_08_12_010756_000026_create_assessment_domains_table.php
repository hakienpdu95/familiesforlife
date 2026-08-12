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
        if (Schema::hasTable('assessment_domains')) {
            return;
        }

        Schema::create('assessment_domains', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('assessment_code', 50)->comment('FK logic tới surveys.assessment_code');
            $table->string('domain_code', 50)->comment('e.g. workflow, sales');
            $table->string('label', 100)->comment('e.g. Quy trình & Vận hành');
            $table->decimal('weight', 5, 4)->comment('Trọng số 0.0000–1.0000, tổng = 1.0000');
            $table->integer('min_score')->comment('Raw score thấp nhất lý thuyết');
            $table->integer('max_score')->comment('Raw score cao nhất lý thuyết');
            $table->tinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            

            // Indexes
            $table->unique(['assessment_code', 'domain_code']);
            $table->index('assessment_code');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_domains');
    }
};