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
        if (Schema::hasTable('ocop_scoring_disqualifier_flags')) {
            return;
        }

        Schema::create('ocop_scoring_disqualifier_flags', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('session_id')->constrained('ocop_scoring_sessions')->cascadeOnDelete();
            $table->foreignId('disqualifier_id')->constrained('ocop_rubric_disqualifiers')->cascadeOnDelete();
            $table->boolean('is_flagged')->default(false);
            $table->timestamps();
            

            // Indexes
            $table->unique(['session_id', 'disqualifier_id'], 'ocop_scoring_disq_flags_session_disq_unique');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_scoring_disqualifier_flags');
    }
};