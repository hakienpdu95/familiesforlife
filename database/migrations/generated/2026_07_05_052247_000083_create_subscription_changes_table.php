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
        if (Schema::hasTable('subscription_changes')) {
            return;
        }

        Schema::create('subscription_changes', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('subscription_id');
            $table->unsignedBigInteger('from_plan_id')->nullable();
            $table->unsignedBigInteger('to_plan_id');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('change_type', 32);
            $table->string('reason', 255)->nullable();
            $table->timestamp('effective_at')->useCurrent();
            $table->decimal('prorate_credit', 15, 2)->default(0);
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index(['organization_id', 'created_at'], 'idx_chg_org');
            $table->index('subscription_id', 'idx_chg_sub');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_changes');
    }
};