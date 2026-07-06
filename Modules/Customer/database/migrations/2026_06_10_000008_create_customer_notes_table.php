<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('customer_id');
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('author_name', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();

            $table->index(['customer_id', 'is_pinned', 'created_at'], 'idx_cn_customer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notes');
    }
};
