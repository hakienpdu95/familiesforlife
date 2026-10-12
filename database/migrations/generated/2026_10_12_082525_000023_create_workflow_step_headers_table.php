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
        if (Schema::hasTable('workflow_step_headers')) {
            return;
        }

        Schema::create('workflow_step_headers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('step_id');
            $table->string('header_key', 128);
            $table->string('header_value', 500);
            

            // Indexes
            $table->index('step_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_step_headers');
    }
};