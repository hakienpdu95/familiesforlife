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
        if (Schema::hasTable('campaign_sandbox_tasks')) {
            return;
        }

        Schema::create('campaign_sandbox_tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('sandbox_task_id');
            $table->tinyInteger('is_required')->default(1);
            $table->unsignedTinyInteger('sort_order')->default(0);
            

            // Indexes
            $table->unique(['campaign_id', 'sandbox_task_id'], 'cst_campaign_task_unique');
            $table->index('campaign_id', 'cst_campaign_index');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_sandbox_tasks');
    }
};