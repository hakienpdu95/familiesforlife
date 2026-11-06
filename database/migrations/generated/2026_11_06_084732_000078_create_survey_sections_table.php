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
        if (Schema::hasTable('survey_sections')) {
            return;
        }

        Schema::create('survey_sections', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('survey_id')->constrained('surveys')->cascadeOnDelete()->comment('FK -> surveys');
            $table->string('title', 255)->comment('Tiêu đề section');
            $table->string('icon', 16)->nullable()->comment('Icon hiển thị');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Thứ tự hiển thị');
            $table->timestamps();
            

            // Indexes
            $table->index(['survey_id', 'sort_order']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_sections');
    }
};