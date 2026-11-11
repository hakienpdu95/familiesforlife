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
        if (Schema::hasTable('system_settings')) {
            return;
        }

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('locale', 10)->default('vi')->comment('Ngôn ngữ giao diện admin + portal: vi | en');
            $table->string('timezone', 64)->default('Asia/Ho_Chi_Minh')->comment('Múi giờ hiển thị (chỉ dùng khi format, không đổi múi giờ lưu DB)');
            $table->string('date_format', 20)->default('d/m/Y')->comment('Định dạng ngày (PHP date format)');
            $table->string('time_format', 20)->default('H:i')->comment('Định dạng giờ (PHP date format)');
            $table->timestamps();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};