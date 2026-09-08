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
        if (Schema::hasTable('subscription_invoices')) {
            return;
        }

        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('subscription_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('invoice_number', 32)->unique();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('VND');
            $table->tinyInteger('status')->default(1);
            $table->date('billing_period_start')->nullable();
            $table->date('billing_period_end')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 64)->nullable();
            $table->string('payment_ref', 191)->nullable();
            $table->text('notes')->nullable();
            $table->string('idempotent_key', 128)->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['organization_id', 'status', 'created_at'], 'idx_inv_org_status');
            $table->index(['status', 'due_date'], 'idx_inv_due');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_invoices');
    }
};