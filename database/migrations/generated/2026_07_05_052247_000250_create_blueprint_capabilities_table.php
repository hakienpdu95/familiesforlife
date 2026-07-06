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
        if (Schema::hasTable('blueprint_capabilities')) {
            return;
        }

        Schema::create('blueprint_capabilities', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
            $table->foreignId('outcome_id')->nullable()->constrained('blueprint_outcomes')->nullOnDelete();
            $table->string('code', 50);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('capability_type', 50)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_capabilities');
    }
};