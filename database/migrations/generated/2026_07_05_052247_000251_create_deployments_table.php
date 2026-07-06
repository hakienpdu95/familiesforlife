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
        if (Schema::hasTable('deployments')) {
            return;
        }

        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('organization_solution_id')->constrained('organization_solutions')->restrictOnDelete();
            $table->foreignId('business_solution_id')->constrained('business_solutions')->restrictOnDelete();
            $table->foreignId('blueprint_id')->constrained('blueprints')->restrictOnDelete();
            $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->restrictOnDelete();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('deployed_by');
            $table->string('status', 20)->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->index(['organization_id', 'status']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};