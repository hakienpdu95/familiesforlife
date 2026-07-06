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
        if (Schema::hasTable('organization_capability_configs')) {
            return;
        }

        Schema::create('organization_capability_configs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_solution_id');
            $table->unsignedBigInteger('blueprint_capability_id');
            $table->boolean('enabled')->default(true);
            $table->string('override_name', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_solution_id', 'blueprint_capability_id'], 'org_capability_configs_unique');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_capability_configs');
    }
};