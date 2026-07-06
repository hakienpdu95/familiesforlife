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
        if (Schema::hasTable('ocop_star_bands')) {
            return;
        }

        Schema::create('ocop_star_bands', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('legal_version', 30)->default('QD26-2026');
            $table->unsignedTinyInteger('star_rank');
            $table->string('label', 100);
            $table->decimal('min_score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->string('authority_level', 20);
            $table->boolean('is_certifiable')->default(false);
            $table->timestamps();
            

            // Indexes
            $table->unique(['legal_version', 'star_rank']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_star_bands');
    }
};