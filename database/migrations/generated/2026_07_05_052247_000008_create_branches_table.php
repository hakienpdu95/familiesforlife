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
        if (Schema::hasTable('branches')) {
            return;
        }

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('branches')->restrictOnDelete();
            $table->string('path', 255)->default('/');
            $table->unsignedTinyInteger('depth')->default(0);
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
            

            // Indexes
            $table->unique(['organization_id', 'code'], 'uq_branch_code');
            $table->index(['organization_id', 'status'], 'idx_branches_org_status');
            $table->index(['organization_id', 'type'], 'idx_branches_org_type');
            $table->index(['organization_id', 'path'], 'idx_branches_org_path');
            $table->index('parent_id', 'idx_branches_parent');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};