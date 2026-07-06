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
        if (Schema::hasTable('rc_offers')) {
            return;
        }

        Schema::create('rc_offers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('application_id')->index();
            $table->decimal('salary_offered', 15, 2);
            $table->char('currency', 3)->default('VND');
            $table->date('start_date');
            $table->smallInteger('probation_days')->default(60);
            $table->text('benefits_note')->nullable();
            $table->date('expire_at')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            

            // Indexes
            $table->index(['application_id', 'status'], 'idx_rc_offer_app');
            $table->index(['expire_at', 'status'], 'idx_rc_offer_expire');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_offers');
    }
};