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
        if (Schema::hasTable('payment_transactions')) {
            return;
        }

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('subscription_invoices')->cascadeOnDelete();
            $table->string('gateway', 32);
            $table->string('direction', 16)->default('inbound');
            $table->string('status', 32)->default('pending');
            $table->string('gateway_ref', 191)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('raw_payload')->nullable();
            $table->string('ip_addr', 45)->nullable();
            $table->timestamps();
            

            // Indexes
            $table->index(['organization_id', 'gateway', 'created_at'], 'idx_ptxn_org');
            $table->index('invoice_id', 'idx_ptxn_inv');
            $table->index('gateway_ref', 'idx_ptxn_ref');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};