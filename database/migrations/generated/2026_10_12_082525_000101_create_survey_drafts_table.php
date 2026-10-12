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
        if (Schema::hasTable('survey_drafts')) {
            return;
        }

        Schema::create('survey_drafts', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->string('respondent_ref')->nullable()->index();
            $table->json('answers');
            $table->unsignedInteger('current_section')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['survey_id', 'respondent_ref']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_drafts');
    }
};