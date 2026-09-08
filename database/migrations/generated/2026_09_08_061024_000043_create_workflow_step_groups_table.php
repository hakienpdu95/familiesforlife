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
        if (Schema::hasTable('workflow_step_groups')) {
            return;
        }

        Schema::create('workflow_step_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->string('name', 128)->nullable();
            $table->unsignedTinyInteger('execute_mode')->default(1);
            $table->unsignedSmallInteger('delay_minutes')->default(0);
            $table->boolean('halt_workflow_on_fail')->default(false);
            

            // Indexes
            $table->index(['workflow_id', 'sort_order']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_step_groups');
    }
};