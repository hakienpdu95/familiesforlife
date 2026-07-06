<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_role_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->foreignId('scope_branch_id')->nullable()->constrained('branches')->restrictOnDelete();
            $table->foreignId('scope_dept_id')->nullable()->constrained('departments')->restrictOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

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
