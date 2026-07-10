<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aicem_monthly_budget_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();

            // '2026-07' — 1 dòng / (org, tháng), khoá lockForUpdate để check-and-reserve O(1) (mục 13.1).
            $table->string('year_month', 7);
            $table->decimal('reserved_usd', 10, 6)->default(0); // đang giữ cho run in-flight
            $table->decimal('settled_usd', 10, 6)->default(0);  // đã chốt của run xong

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'year_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aicem_monthly_budget_usage');
    }
};
