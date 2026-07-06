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
        if (Schema::hasTable('blueprint_resource_links')) {
            return;
        }

        Schema::create('blueprint_resource_links', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
            $table->foreignId('checklist_id')->nullable()->constrained('blueprint_checklists')->nullOnDelete();
            $table->string('resource_type', 50);
            $table->unsignedBigInteger('resource_id');
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->index(['resource_type', 'resource_id']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_resource_links');
    }
};