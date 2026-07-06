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
        if (Schema::hasTable('ocop_rubric_sections')) {
            return;
        }

        Schema::create('ocop_rubric_sections', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('rubric_version_id')->constrained('ocop_rubric_versions')->cascadeOnDelete();
            $table->string('code', 5);
            $table->string('label', 255);
            $table->decimal('max_score', 5, 2);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->unique(['rubric_version_id', 'code']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_rubric_sections');
    }
};