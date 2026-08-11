<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('newsletter_subscribers')) {
            return;
        }

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('full_name', 150);
            $table->string('email', 255)->unique();
            $table->string('resend_contact_id', 36)->nullable();
            $table->string('status', 20)->default('active');
            $table->string('source', 50)->nullable();
            $table->timestamp('subscribed_at')->useCurrent();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('resend_contact_id');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
