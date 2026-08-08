<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GEO đợt 7 (2026-08-08, đối chiếu spec/giadinh.md — "Product-Led Content Strategy: Turning
 * Content Into Pipeline") — khối "Lời chứng thực khách hàng" (Testimonial). Đã khảo sát kỹ trước
 * khi thêm: KHÔNG tái dùng Citation (khác ngữ nghĩa — Citation = trích dẫn số liệu/nguồn nghiên
 * cứu bên thứ 3 phục vụ "citation engineering", Testimonial = lời chứng thực khách hàng dùng sản
 * phẩm/dịch vụ phục vụ social proof/conversion; view Citation cũng không có chỗ cho ảnh đại
 * diện/chức danh/công ty).
 *
 * Lưu TRỰC TIẾP trên post_content_blocks (không tạo bảng con riêng như Howto/Comparison) — cùng
 * quyết định đã áp dụng cho Citation: "1 block = 1 lời chứng thực duy nhất, không phải danh sách
 * item lặp lại" (khác Faq/Howto/Comparison cần bảng con vì có N item/step/row bên trong).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_content_blocks', function (Blueprint $table): void {
            if (! Schema::hasColumn('post_content_blocks', 'testimonial_quote')) {
                $table->text('testimonial_quote')->nullable()->after('comparison_block_id')
                    ->comment('Nội dung lời chứng thực — chỉ dùng khi type=testimonial');
            }
            if (! Schema::hasColumn('post_content_blocks', 'testimonial_person_name')) {
                $table->string('testimonial_person_name', 150)->nullable()->after('testimonial_quote')
                    ->comment('Tên người chứng thực (bắt buộc cùng testimonial_quote khi type=testimonial)');
            }
            if (! Schema::hasColumn('post_content_blocks', 'testimonial_person_title')) {
                $table->string('testimonial_person_title', 150)->nullable()->after('testimonial_person_name')
                    ->comment('Chức danh người chứng thực, VD "Mẹ 2 con" — không bắt buộc');
            }
            if (! Schema::hasColumn('post_content_blocks', 'testimonial_company_name')) {
                $table->string('testimonial_company_name', 150)->nullable()->after('testimonial_person_title')
                    ->comment('Tên công ty/thương hiệu liên quan — không bắt buộc');
            }
            if (! Schema::hasColumn('post_content_blocks', 'testimonial_avatar_url')) {
                $table->string('testimonial_avatar_url', 2048)->nullable()->after('testimonial_company_name')
                    ->comment('URL ảnh đại diện — dán link trực tiếp, không qua Media Library (cùng nguyên tắc Video §0: nguồn ảnh có sẵn, tự tải/lưu lại là dư thừa)');
            }
            if (! Schema::hasColumn('post_content_blocks', 'testimonial_result_metric')) {
                $table->string('testimonial_result_metric', 150)->nullable()->after('testimonial_avatar_url')
                    ->comment('Kết quả đạt được cụ thể, VD "Tiết kiệm 5 giờ/tuần" — không bắt buộc');
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_content_blocks', function (Blueprint $table): void {
            foreach (['testimonial_quote', 'testimonial_person_name', 'testimonial_person_title', 'testimonial_company_name', 'testimonial_avatar_url', 'testimonial_result_metric'] as $column) {
                if (Schema::hasColumn('post_content_blocks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
