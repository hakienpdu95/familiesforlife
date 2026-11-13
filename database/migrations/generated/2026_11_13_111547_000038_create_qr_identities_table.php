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
        if (Schema::hasTable('qr_identities')) {
            return;
        }

        Schema::create('qr_identities', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('qr_code', 15)->unique()->comment('Mã in trên tem — chuỗi Base62 ngẫu nhiên, bất biến');
            $table->string('target_type', 100)->comment('Morph type của đối tượng gốc (ocop_product, ...)');
            $table->unsignedBigInteger('target_id')->comment('Morph id của đối tượng gốc — KHÔNG FK để đối tượng bị xoá cứng vẫn giữ mã');
            $table->enum('status', ['active', 'revoked'])->default('active')->index()->comment('Trạng thái mã');
            $table->json('snapshot')->nullable()->comment('Thông tin đối tượng tại thời điểm phát hành tem — dùng khi đối tượng gốc không còn');
            $table->timestamp('revoked_at')->nullable()->comment('Thời điểm thu hồi mã');
            $table->timestamps();
            

            // Indexes
            $table->index(['target_type', 'target_id'], 'idx_qr_identities_target');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_identities');
    }
};