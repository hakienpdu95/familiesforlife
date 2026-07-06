<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('branches')->restrictOnDelete();
            $table->string('path', 255)->default('/');
            $table->unsignedTinyInteger('depth')->default(0);
            // manager_id added in separate migration after employees table exists
            $table->unsignedInteger('order_column')->default(0);
            $table->string('name');
            $table->string('code', 50);
            $table->string('type', 30)->default('branch');
            $table->string('status', 20)->default('active');
            $table->string('tax_code', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('fax', 20)->nullable();
            $table->char('province_code', 2)->nullable();
            $table->char('ward_code', 5)->nullable();
            $table->string('address', 500)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('timezone', 50)->nullable();
            $table->char('currency', 3)->nullable();
            $table->date('opened_at')->nullable();
            $table->date('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Unique constraints
            $table->unique(['organization_id', 'code'], 'uq_branch_code');

            // Indexes
            $table->index(['organization_id', 'status'], 'idx_branches_org_status');
            $table->index(['organization_id', 'type'], 'idx_branches_org_type');
            $table->index(['organization_id', 'path'], 'idx_branches_org_path');
            $table->index('parent_id', 'idx_branches_parent');

            // Geographic FK
            $table->foreign('province_code')->references('province_code')->on('provinces')->nullOnDelete();
            $table->foreign('ward_code')->references('ward_code')->on('wards')->nullOnDelete();
        });

        // CHECK constraints (MySQL 8.0.16+)
        DB::statement("ALTER TABLE branches ADD CONSTRAINT chk_branch_type CHECK (type IN ('headquarters','regional_office','branch','store','warehouse'))");
        DB::statement("ALTER TABLE branches ADD CONSTRAINT chk_branch_status CHECK (status IN ('active','inactive','closed'))");
        // chk_branch_no_self_ref: enforced at app layer (MySQL 8 disallows CHECK on auto-increment PK)
        DB::statement("ALTER TABLE branches ADD CONSTRAINT chk_branch_depth CHECK (depth >= 0 AND depth <= 2)");
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
