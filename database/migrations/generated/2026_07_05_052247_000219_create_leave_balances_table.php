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
        if (Schema::hasTable('leave_balances')) {
            return;
        }

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations');
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('policy_id')->constrained('leave_policies');
            $table->string('leave_type', 20);
            $table->smallInteger('year');
            $table->decimal('entitled_days', 5, 1);
            $table->decimal('used_days', 5, 1)->default(0);
            $table->decimal('pending_days', 5, 1)->default(0);
            $table->decimal('carried_over', 5, 1)->default(0);
            $table->decimal('adjusted', 5, 1)->default(0);
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['employee_id', 'policy_id', 'year'], 'idx_leave_balances_unique');
            $table->index(['employee_id', 'year', 'leave_type'], 'idx_leave_balances_emp_year');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};