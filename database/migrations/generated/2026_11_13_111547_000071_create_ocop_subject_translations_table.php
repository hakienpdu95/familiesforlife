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
        if (Schema::hasTable('ocop_subject_translations')) {
            return;
        }

        Schema::create('ocop_subject_translations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('ocop_subject_id')->constrained('ocop_subjects')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 255)->nullable();
            $table->longText('story')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['ocop_subject_id', 'locale'], 'uq_ocop_subject_translations_locale');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_subject_translations');
    }
};