<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('organization_id');

            $table->string('full_name', 191);
            $table->string('email', 191)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('phone_alt', 32)->nullable();

            $table->string('company', 191)->nullable();
            $table->string('job_title', 128)->nullable();
            $table->string('website', 500)->nullable();

            $table->string('address', 500)->nullable();
            $table->string('ward_code', 8)->nullable();
            $table->string('ward_name', 64)->nullable();
            $table->string('district_code', 8)->nullable();
            $table->string('district_name', 64)->nullable();
            $table->string('province_code', 8)->nullable();
            $table->string('province_name', 64)->nullable();
            $table->char('country_code', 2)->default('VN');

            $table->char('dedup_hash', 32)->nullable();
            $table->unsignedSmallInteger('lead_count')->default(0);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'dedup_hash'], 'uq_contact_org_dedup');
            $table->index(['organization_id', 'email'], 'idx_contact_email');
            $table->index(['organization_id', 'phone'], 'idx_contact_phone');
            $table->index(['organization_id', 'full_name'], 'idx_contact_full_name');
            $table->index(['organization_id', DB::raw('company(32)')], 'idx_contact_company');
            $table->index(['organization_id', 'province_code'], 'idx_contact_province');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_contacts');
    }
};
