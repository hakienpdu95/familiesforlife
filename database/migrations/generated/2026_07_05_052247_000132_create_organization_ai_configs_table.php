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
        if (Schema::hasTable('organization_ai_configs')) {
            return;
        }

        Schema::create('organization_ai_configs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_solution_id');
            $table->string('ai_capability_code', 100);
            $table->boolean('enabled')->default(true);
            $table->unsignedBigInteger('ai_agent_id')->nullable();
            $table->unsignedBigInteger('ai_prompt_id')->nullable();
            $table->string('provider', 50)->nullable();
            $table->decimal('cost_limit', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_solution_id', 'ai_capability_code'], 'org_ai_configs_unique');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_ai_configs');
    }
};