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
        if (Schema::hasTable('activity_log_http')) {
            return;
        }

        Schema::create('activity_log_http', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('log_id');
            $table->unsignedTinyInteger('http_method')->comment('1=GET 2=POST 3=PUT 4=PATCH 5=DELETE 6=HEAD 7=OPTIONS');
            $table->string('url', 2000);
            $table->string('route_name', 191)->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedSmallInteger('duration_ms')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->unique('log_id', 'uq_log');
            $table->index('route_name', 'idx_route');
            $table->index('status_code', 'idx_status');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log_http');
    }
};