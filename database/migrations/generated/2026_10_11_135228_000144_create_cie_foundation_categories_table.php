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
        if (Schema::hasTable('cie_foundation_categories')) {
            return;
        }

        Schema::create('cie_foundation_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('foundation_id')->constrained('cie_category_foundations')->cascadeOnDelete();
            $table->foreignId('post_category_id')->unique()->constrained('post_categories')->cascadeOnDelete();
            $table->timestamps();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('cie_foundation_categories');
    }
};