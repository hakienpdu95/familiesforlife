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
        if (Schema::hasTable('workforce_profile_histories')) {
            return;
        }

        Schema::create('workforce_profile_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('workforce_profile_id');
            $table->string('event_type', 30)->comment('assessment|kpi|sandbox|certification|impact');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_type', 150)->nullable()->comment('FQCN model nguồn');
            $table->decimal('tdwcf_score_before', 5, 2)->nullable();
            $table->decimal('tdwcf_score_after', 5, 2)->nullable();
            $table->string('maturity_level_before', 64)->nullable();
            $table->string('maturity_level_after', 64)->nullable();
            $table->decimal('change_delta', 6, 2)->nullable()->comment('tdwcf_score_after - tdwcf_score_before (CGI base)');
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
            

            // Indexes
            $table->index(['workforce_profile_id', 'event_type'], 'idx_wph_profile_event');
            $table->index('recorded_at', 'idx_wph_recorded_at');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workforce_profile_histories');
    }
};