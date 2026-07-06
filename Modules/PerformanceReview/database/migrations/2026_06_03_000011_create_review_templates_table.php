<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('period_type', 20)->default('quarterly');
            $table->string('apply_to_function', 50)->nullable();
            $table->unsignedTinyInteger('rating_scale')->default(5);
            $table->tinyInteger('is_system')->default(0);
            $table->tinyInteger('is_locked')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'is_active'], 'idx_review_templates_org');
        });

        DB::statement("ALTER TABLE review_templates ADD CONSTRAINT chk_rt_period_type CHECK (period_type IN ('monthly','quarterly','semi_annual','annual','probation','custom'))");
        DB::statement("ALTER TABLE review_templates ADD CONSTRAINT chk_rt_rating_scale CHECK (rating_scale IN (5, 10))");
    }

    public function down(): void
    {
        Schema::dropIfExists('review_templates');
    }
};
