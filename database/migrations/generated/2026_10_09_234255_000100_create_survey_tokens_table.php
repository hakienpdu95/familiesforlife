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
        if (Schema::hasTable('survey_tokens')) {
            return;
        }

        Schema::create('survey_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('survey_id')->constrained('surveys')->cascadeOnDelete()->comment('FK -> surveys');
            $table->string('name', 150)->comment('Tiêu đề');
            $table->string('token', 80)->unique()->comment('Lưu hash, hiển thị plaintext 1 lần');
            $table->text('token_encrypted')->nullable()->comment('Token Encrypted');
            $table->boolean('is_active')->default(true)->index()->comment('Trạng thái hoạt động');
            $table->timestamp('last_used_at')->nullable()->comment('Cập nhật mỗi lần token được dùng');
            $table->timestamp('expires_at')->nullable()->comment('Cập nhật null là không hết hạn');
            $table->timestamps();
            

            // Indexes
            $table->index('survey_id');
            $table->index(['survey_id', 'is_active']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_tokens');
    }
};