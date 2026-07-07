<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nguồn sự thật cho nội dung hỗn hợp text + khối sản phẩm (block-composer, kiểu
     * Gutenberg) — thay cho việc nhúng placeholder HTML vào 1 cột `content` lớn rồi
     * parse lại bằng DOMDocument mỗi lần lưu/hiển thị. Mỗi dòng = 1 block theo đúng
     * thứ tự hiển thị (`sort_order`); `type=text` đọc `text_html`, `type=product` join
     * sang `post_product_blocks` (schema đó giữ nguyên, không đổi).
     */
    public function up(): void
    {
        Schema::create('post_content_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->string('type', 20); // ContentBlockType: text|product
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->longText('text_html')->nullable(); // dùng khi type=text — đã sanitize trước khi lưu
            $table->foreignId('product_block_id')->nullable()
                ->constrained('post_product_blocks')->cascadeOnDelete(); // dùng khi type=product
            $table->timestamps();

            $table->index(['article_id', 'sort_order'], 'idx_post_cb_article_order');
        });

        Schema::table('post_articles', function (Blueprint $table) {
            $table->dropColumn('content');
        });
    }

    public function down(): void
    {
        Schema::table('post_articles', function (Blueprint $table) {
            $table->longText('content')->nullable();
        });

        Schema::dropIfExists('post_content_blocks');
    }
};
