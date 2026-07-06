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
        if (Schema::hasTable('ai_monthly_usages')) {
            return;
        }

        Schema::create('ai_monthly_usages', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations');
            $table->char('year_month', 7);
            $table->foreignId('agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->string('task_type', 60)->nullable();
            $table->unsignedInteger('total_requests')->default(0);
            $table->unsignedInteger('successful_requests')->default(0);
            $table->unsignedBigInteger('total_input_tokens')->default(0);
            $table->unsignedBigInteger('total_output_tokens')->default(0);
            $table->unsignedBigInteger('total_tokens')->default(0);
            $table->decimal('total_cost_usd', 12, 6)->default(0);
            $table->timestamp('updated_at')->nullable();
            

            // Indexes
            $table->unique(['organization_id', 'year_month', 'agent_id'], 'uq_usage_org_month_agent');
            $table->index(['organization_id', 'year_month'], 'idx_usage_org_month');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_monthly_usages');
    }
};