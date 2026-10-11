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
        if (Schema::hasTable('workflow_conditions')) {
            return;
        }

        Schema::create('workflow_conditions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->string('field', 128);
            $table->string('operator', 32);
            $table->string('value', 500)->nullable();
            $table->unsignedTinyInteger('value_type')->default(1);
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index(['workflow_id', 'sort_order']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_conditions');
    }
};