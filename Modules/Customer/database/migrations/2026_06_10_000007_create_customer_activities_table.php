<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->tinyInteger('type')->unsigned();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('outcome', 500)->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name', 255)->nullable();
            $table->dateTime('created_at');

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();

            $table->index(['customer_id', 'created_at'], 'idx_ca_customer');
            $table->index(['organization_id', 'type', 'created_at'], 'idx_ca_org_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_activities');
    }
};
