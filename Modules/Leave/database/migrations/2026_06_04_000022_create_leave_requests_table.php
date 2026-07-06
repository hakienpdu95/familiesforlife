<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained('organizations');
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('balance_id')->constrained('leave_balances');
            $table->string('leave_type', 20);
            $table->date('date_from');
            $table->date('date_to');
            $table->decimal('days_count', 5, 1);
            $table->string('status', 20)->default('pending');
            $table->text('reason')->nullable();
            $table->text('attachment_url')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status', 'date_from'], 'idx_leave_requests_emp_status');
            $table->index(['approved_by', 'status'], 'idx_leave_requests_approver');
            $table->index(['organization_id', 'date_from', 'date_to'], 'idx_leave_requests_org_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
