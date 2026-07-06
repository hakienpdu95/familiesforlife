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
        if (Schema::hasTable('organization_role_mappings')) {
            return;
        }

        Schema::create('organization_role_mappings', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_solution_id');
            $table->string('blueprint_role_code', 100);
            $table->unsignedBigInteger('organization_role_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('mapping_type', 30)->default('role');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_solution_id', 'blueprint_role_code'], 'org_role_mappings_unique');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_role_mappings');
    }
};