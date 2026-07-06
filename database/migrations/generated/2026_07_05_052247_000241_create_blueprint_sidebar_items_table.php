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
        if (Schema::hasTable('blueprint_sidebar_items')) {
            return;
        }

        Schema::create('blueprint_sidebar_items', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('blueprint_sidebar_items')->cascadeOnDelete();
            $table->string('module_key', 100);
            $table->string('label', 255);
            $table->string('icon', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->index(['blueprint_version_id', 'sort_order']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_sidebar_items');
    }
};