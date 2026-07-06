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
        if (Schema::hasTable('vertical_config_items')) {
            return;
        }

        Schema::create('vertical_config_items', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('vertical_code', 50);
            $table->string('config_group', 50);
            $table->string('code', 50);
            $table->string('label', 255);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            

            // Indexes
            $table->unique(['organization_id', 'vertical_code', 'config_group', 'code'], 'uq_vertical_config_item');
            $table->index(['organization_id', 'vertical_code', 'config_group', 'is_active'], 'idx_vertical_config_lookup');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('vertical_config_items');
    }
};