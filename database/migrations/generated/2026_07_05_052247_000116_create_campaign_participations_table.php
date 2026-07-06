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
        if (Schema::hasTable('campaign_participations')) {
            return;
        }

        Schema::create('campaign_participations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->string('status', 20)->default('in_progress')->comment('in_progress | completed | abandoned | declined');
            $table->decimal('result_tdwcf_score', 5, 2)->nullable();
            $table->string('result_maturity_level', 64)->nullable();
            $table->decimal('result_sandbox_avg', 5, 2)->nullable();
            $table->unsignedBigInteger('passport_entry_id')->nullable();
            $table->unsignedTinyInteger('org_rating')->nullable()->comment('1–5 sao');
            $table->string('org_note', 500)->nullable();
            $table->string('org_action', 30)->nullable()->comment('shortlisted | invited | hired | rejected');
            $table->timestamp('org_action_at')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['campaign_id', 'user_id'], 'cp_campaign_user_unique');
            $table->index('user_id', 'cp_user_index');
            $table->index('campaign_id', 'cp_campaign_index');
            $table->index('status', 'cp_status_index');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_participations');
    }
};