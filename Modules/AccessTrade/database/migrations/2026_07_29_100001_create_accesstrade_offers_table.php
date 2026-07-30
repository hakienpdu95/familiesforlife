<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot của AccessTrade Publisher API "offers_informations" — gộp chung 2 tài liệu
 * "vouchers/coupons/deals" và "khuyến mãi đang hoạt động" (cùng 1 endpoint, chỉ khác filter
 * coupon/status) vào 1 bảng duy nhất. has_coupon phân biệt "voucher/coupon" khỏi khuyến mãi
 * thường; status phản ánh nguyên trạng thái AccessTrade trả về ở lần đồng bộ gần nhất (đè lại
 * mỗi lần sync, không có quy trình xoá/duyệt riêng — tài sản nền tảng, không organization_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accesstrade_offers', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 64)->unique(); // "id" trả về bởi AccessTrade

            $table->string('name', 255);
            $table->text('content')->nullable();
            $table->string('merchant', 120)->nullable();
            $table->string('domain', 255)->nullable();
            $table->string('link', 2048)->nullable();
            $table->string('aff_link', 2048)->nullable();
            $table->string('image', 2048)->nullable();

            $table->json('categories')->nullable();
            $table->json('coupons')->nullable();
            $table->json('banners')->nullable();

            $table->boolean('has_coupon')->default(false);
            $table->boolean('status')->default(true); // true = đang active theo AccessTrade

            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();

            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'has_coupon'], 'idx_accesstrade_offers_status_coupon');
            $table->index('merchant', 'idx_accesstrade_offers_merchant');
            $table->index('end_time', 'idx_accesstrade_offers_end_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accesstrade_offers');
    }
};
