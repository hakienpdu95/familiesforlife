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
        if (Schema::hasTable('organization_solutions')) {
            return;
        }

        Schema::create('organization_solutions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('business_solution_id')->constrained('business_solutions')->restrictOnDelete();
            $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->restrictOnDelete();
            $table->string('name', 255);
            $table->unsignedBigInteger('owner_id');
            $table->string('status', 20)->default('draft');
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_id', 'business_solution_id'], 'org_solutions_org_business_solution_unique');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_solutions');
    }
};