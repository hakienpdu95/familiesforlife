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
        if (Schema::hasTable('user_role_scopes')) {
            return;
        }

        Schema::create('user_role_scopes', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->foreignId('scope_branch_id')->nullable()->constrained('branches')->restrictOnDelete();
            $table->foreignId('scope_dept_id')->nullable()->constrained('departments')->restrictOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['user_id', 'role_id', 'scope_branch_id', 'scope_dept_id'], 'uq_scope');
            $table->index(['organization_id', 'user_id'], 'idx_role_scopes_user');
            $table->index(['scope_branch_id', 'scope_dept_id'], 'idx_role_scopes_branch');
            $table->index('expires_at', 'idx_role_scopes_expires');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role_scopes');
    }
};