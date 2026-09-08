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
        if (Schema::hasTable('customer_notes')) {
            return;
        }

        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('customer_id');
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('author_name', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['customer_id', 'is_pinned', 'created_at'], 'idx_cn_customer');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notes');
    }
};