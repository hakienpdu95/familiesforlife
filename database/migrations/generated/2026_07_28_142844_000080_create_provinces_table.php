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
        if (Schema::hasTable('provinces')) {
            return;
        }

        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('name', 255)->index()->comment('Tên tỉnh/thành phố');
            $table->string('short_name', 255)->comment('Tên ngắn gọn của tỉnh/thành phố');
            $table->string('logo', 255)->nullable()->comment('Logo tỉnh');
            $table->char('province_code', 2)->unique()->index()->comment('Mã tỉnh/thành phố');
            $table->enum('place_type', ['thanh-pho', 'tinh'])->default('tinh')->index()->comment('Loại: Thành phố Trung Ương hoặc Tỉnh');
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete()->comment('Thuộc vùng — FK tới regions.id');
            $table->string('country', 2)->default('VN')->index()->comment('Mã quốc gia');
            $table->boolean('is_active')->default(true)->index()->comment('Trạng thái hoạt động');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index('region_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};