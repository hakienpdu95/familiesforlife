<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('post_howto_steps')) {
            return;
        }

        Schema::create('post_howto_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('howto_block_id')->constrained('post_howto_blocks')->cascadeOnDelete();
            $table->string('name', 200)->comment('Tên bước (VD: Bước 1 — Rửa tay sạch)');
            $table->text('text')->comment('Nội dung chi tiết của bước');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['howto_block_id', 'sort_order'], 'idx_post_hs_block_order');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('post_howto_steps');
    }
};
