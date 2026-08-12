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
        if (Schema::hasTable('aicem_monthly_budget_usage')) {
            return;
        }

        Schema::create('aicem_monthly_budget_usage', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('year_month', 7);
            $table->decimal('reserved_usd', 10, 6)->default(0);
            $table->decimal('settled_usd', 10, 6)->default(0);
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_id', 'year_month']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('aicem_monthly_budget_usage');
    }
};