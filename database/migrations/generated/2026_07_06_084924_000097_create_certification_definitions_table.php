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
        if (Schema::hasTable('certification_definitions')) {
            return;
        }

        Schema::create('certification_definitions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id')->nullable()->comment('null = global template');
            $table->string('cert_code', 50)->unique()->comment('e.g. TDWCF_FOUNDATION, AI_SALES_PRACTITIONER');
            $table->string('cert_type_code', 30)->comment('AI_ADMIN|AI_HR|AI_SALES|AI_FINANCE|AI_DATA|AI_MANAGER|AI_LEADER');
            $table->string('name', 200);
            $table->string('level_code', 30)->comment('FOUNDATION|PRACTITIONER|PROFESSIONAL|LEADER');
            $table->unsignedTinyInteger('level_order')->comment('1=Foundation, 2=Practitioner, 3=Professional, 4=Leader');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('validity_months')->nullable()->comment('Foundation/Practitioner: 24, Professional/Leader: 36');
            $table->decimal('min_workforce_score', 5, 2)->nullable();
            $table->decimal('min_kpi_achievement_pct', 5, 2)->nullable();
            $table->unsignedSmallInteger('min_sandbox_hours')->nullable();
            $table->decimal('min_sandbox_score', 5, 2)->nullable();
            $table->boolean('requires_impact_score')->default(false);
            $table->boolean('requires_portfolio_approval')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            

            // Indexes
            $table->index('cert_type_code', 'idx_certdef_type');
            $table->index('level_code', 'idx_certdef_level');
            $table->index('organization_id', 'idx_certdef_org');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('certification_definitions');
    }
};