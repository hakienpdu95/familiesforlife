<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('events')) {
            return;
        }

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('category_id')->constrained('event_categories')->restrictOnDelete()->comment('Danh mục sự kiện');
            $table->string('title', 150)->comment('Tiêu đề đầy đủ');
            $table->string('short_title', 55)->comment('Tiêu đề rút gọn, tối đa 55 ký tự');
            $table->string('slug', 180)->unique()->comment('Slug URL công khai');
            $table->text('description')->comment('Mô tả sự kiện — không được chứa link (validate ở Action)');
            $table->date('start_date')->comment('Ngày bắt đầu');
            $table->date('end_date')->comment('Ngày kết thúc');
            $table->time('start_time')->nullable()->comment('Giờ bắt đầu — null = cả ngày');
            $table->time('end_time')->nullable()->comment('Giờ kết thúc');
            $table->string('location_type', 10)->comment('EventLocationType: physical|online');
            $table->string('venue_name', 150)->nullable()->comment('Tên địa điểm — bắt buộc khi physical (validate ở Action)');
            $table->string('venue_address', 255)->nullable()->comment('Số nhà/tên đường — bắt buộc khi physical');
            $table->char('province_code', 2)->nullable()->comment('Mã tỉnh/thành — không FK cứng, cùng pattern customers.province_code');
            $table->string('province_name', 255)->nullable()->comment('Tên tỉnh/thành denormalized tại thời điểm chọn');
            $table->char('ward_code', 5)->nullable()->comment('Mã phường/xã — không FK cứng');
            $table->string('ward_name', 255)->nullable()->comment('Tên phường/xã denormalized');
            $table->string('full_address', 500)->nullable()->comment('Chuỗi hiển thị dựng sẵn — venue_address, ward_name, province_name');
            $table->decimal('latitude', 10, 7)->nullable()->comment('Toạ độ bản đồ (Phase 4)');
            $table->decimal('longitude', 10, 7)->nullable()->comment('Toạ độ bản đồ (Phase 4)');
            $table->string('online_url', 500)->nullable()->comment('Link tham gia — bắt buộc khi online');
            $table->string('website_url', 500)->comment('Link vé/đăng ký hoặc website chính thức');
            $table->string('price_type', 10)->comment('EventPriceType: free|single|range');
            $table->decimal('price_amount', 10, 2)->nullable()->comment('Giá vé — dùng khi single');
            $table->decimal('price_min', 10, 2)->nullable()->comment('Giá thấp nhất — dùng khi range');
            $table->decimal('price_max', 10, 2)->nullable()->comment('Giá cao nhất — dùng khi range');
            $table->string('poster_path', 255)->comment('Đường dẫn ảnh poster');
            $table->string('poster_alt', 150)->nullable()->comment('Alt text — SEO/accessibility');
            $table->unsignedInteger('poster_width')->nullable()->comment('Chiều rộng ảnh (px)');
            $table->unsignedInteger('poster_height')->nullable()->comment('Chiều cao ảnh (px)');
            $table->unsignedInteger('poster_size_bytes')->nullable()->comment('Dung lượng file (bytes)');
            $table->string('status', 20)->default('submitted')->comment('EventStatus');
            $table->boolean('is_featured')->default(false)->comment('Ghim hero trang chủ');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->comment('Người duyệt');
            $table->timestamp('approved_at')->nullable()->comment('Thời gian duyệt');
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete()->comment('Người từ chối');
            $table->timestamp('rejected_at')->nullable()->comment('Thời gian từ chối');
            $table->string('rejected_reason', 255)->nullable()->comment('Lý do từ chối');
            $table->timestamp('published_at')->nullable()->comment('Thời gian xuất bản');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('Người tạo — null nếu độc giả tự nộp qua form public');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->comment('Người sửa cuối');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'start_date'], 'idx_events_status_start');
            $table->index(['status', 'end_date'], 'idx_events_status_end');
            $table->index(['category_id', 'status', 'start_date'], 'idx_events_cat_status_start');
            $table->index(['status', 'is_featured', 'start_date'], 'idx_events_status_featured');
            $table->index(['province_code', 'status', 'start_date'], 'idx_events_province_status');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
