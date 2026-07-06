<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocop_star_bands', function (Blueprint $table) {
            $table->id();
            $table->string('legal_version', 30)->default('QD26-2026'); // cho phép versioning khi có nghị định mới
            $table->unsignedTinyInteger('star_rank');                  // 1..5
            $table->string('label', 100);                              // "Hạng 3 sao (cấp tỉnh)"
            $table->decimal('min_score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->string('authority_level', 20);                     // 'commune_screen_only'|'province'|'central'
            $table->boolean('is_certifiable')->default(false);         // true chỉ star_rank >= 3 (Điều 4.3)
            $table->timestamps();

            $table->unique(['legal_version', 'star_rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_star_bands');
    }
};
