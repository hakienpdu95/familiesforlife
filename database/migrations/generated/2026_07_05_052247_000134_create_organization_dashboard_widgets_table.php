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
        if (Schema::hasTable('organization_dashboard_widgets')) {
            return;
        }

        Schema::create('organization_dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_solution_id');
            $table->unsignedBigInteger('blueprint_analytic_id')->nullable();
            $table->string('widget_type', 50)->default('metric');
            $table->string('title', 255);
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['organization_solution_id', 'sort_order'], 'org_dashboard_widgets_sort_index');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_dashboard_widgets');
    }
};