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
        if (Schema::hasTable('customer_activities')) {
            return;
        }

        Schema::create('customer_activities', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedTinyInteger('type');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('outcome', 500)->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name', 255)->nullable();
            $table->dateTime('created_at');
            

            // Indexes
            $table->index(['customer_id', 'created_at'], 'idx_ca_customer');
            $table->index(['organization_id', 'type', 'created_at'], 'idx_ca_org_type');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_activities');
    }
};