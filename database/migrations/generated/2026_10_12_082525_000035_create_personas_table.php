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
        if (Schema::hasTable('personas')) {
            return;
        }

        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('assessment_code', 50)->comment('FK logic tới assessments.assessment_code');
            $table->string('persona_code', 100);
            $table->string('label', 255);
            $table->text('description')->nullable();
            $table->tinyInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->unique(['assessment_code', 'persona_code'], 'uq_persona');
            $table->index('assessment_code');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};