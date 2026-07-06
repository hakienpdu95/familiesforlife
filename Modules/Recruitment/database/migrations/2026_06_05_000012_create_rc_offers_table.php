<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rc_offers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique();
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

            $table->foreign('application_id')->references('id')->on('rc_applications')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['application_id', 'status'], 'idx_rc_offer_app');
            $table->index(['expire_at', 'status'], 'idx_rc_offer_expire');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_offers');
    }
};
