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
        if (Schema::hasTable('rc_pipeline_stages')) {
            return;
        }

        Schema::create('rc_pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('org_id')->index();
            $table->string('name', 100);
            $table->string('stage_type', 30)->index();
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('require_score')->default(false);
            $table->boolean('send_notification')->default(true);
            $table->char('color_hex', 7)->nullable();
            $table->boolean('is_active')->default(true);
            

            // Indexes
            $table->index(['org_id', 'sort_order']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_pipeline_stages');
    }
};