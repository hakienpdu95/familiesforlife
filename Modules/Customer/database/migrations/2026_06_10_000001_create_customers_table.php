<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('organization_id')->index();

            $table->tinyInteger('customer_type')->default(1); // Individual=1, Business=2

            // Chung
            $table->string('display_name');
            $table->string('primary_email', 255)->nullable();
            $table->string('primary_phone', 30)->nullable();
            $table->string('province_code', 10)->nullable();
            $table->string('full_address', 500)->nullable();
            $table->string('website', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('avatar_url', 500)->nullable();

            // Phân loại
            $table->tinyInteger('lifecycle_stage')->default(2); // Active
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->unsignedInteger('activity_count')->default(0);

            // Individual
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->tinyInteger('gender')->nullable();
            $table->date('date_of_birth')->nullable();

            // Business
            $table->string('company_name', 255)->nullable();
            $table->string('tax_code', 50)->nullable();
            $table->string('industry', 100)->nullable();
            $table->tinyInteger('company_size')->nullable();
            $table->string('representative_name', 255)->nullable();
            $table->string('representative_title', 150)->nullable();

            // Truy vết
            $table->char('dedup_hash', 32)->nullable();
            $table->unsignedBigInteger('converted_from_lead_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['organization_id', 'dedup_hash'], 'uq_customer_org_dedup');
            $table->index(['organization_id', 'primary_email'], 'idx_customer_email');
            $table->index(['organization_id', 'primary_phone'], 'idx_customer_phone');
            $table->index(['organization_id', 'lifecycle_stage', 'customer_type', 'assigned_to'], 'idx_customer_list');
            $table->index(['organization_id', 'last_activity_at'], 'idx_customer_activity');
            $table->index(['organization_id', 'source_id'], 'idx_customer_source');
            $table->index(['organization_id', 'province_code'], 'idx_customer_province');

            $table->foreign('source_id')->references('id')->on('lead_sources')->nullOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
