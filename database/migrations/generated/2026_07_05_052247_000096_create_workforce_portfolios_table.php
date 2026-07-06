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
        if (Schema::hasTable('workforce_portfolios')) {
            return;
        }

        Schema::create('workforce_portfolios', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('workforce_profile_id');
            $table->string('item_type', 30)->comment('assessment_result|sandbox_result|case_study|improvement_report|impact_data|work_sample');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('evidence_url', 500)->nullable();
            $table->unsignedBigInteger('kc_item_id')->nullable()->comment('FK kc_items — nếu đã đăng lên Knowledge Center');
            $table->string('approval_status', 20)->default('pending')->comment('pending|approved|rejected');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->index(['workforce_profile_id', 'item_type'], 'idx_wfport_profile_type');
            $table->index(['workforce_profile_id', 'approval_status'], 'idx_wfport_profile_status');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workforce_portfolios');
    }
};