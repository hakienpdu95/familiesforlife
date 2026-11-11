<?php

namespace Modules\Post\Enums;

enum ContentBlockType: string
{
    case Text = 'text';
    case Product = 'product';
    case Faq = 'faq';
    case Citation = 'citation';
    case Howto = 'howto';
    case Comparison = 'comparison';
    case Testimonial = 'testimonial';

    public function label(): string
    {
        return match ($this) {
            self::Text => __('Đoạn văn bản'),
            self::Product => __('Khối sản phẩm'),
            self::Faq => __('Câu hỏi thường gặp'),
            self::Citation => __('Trích dẫn có nguồn'),
            self::Howto => __('Hướng dẫn từng bước'),
            self::Comparison => __('Bảng so sánh'),
            self::Testimonial => __('Lời chứng thực khách hàng'),
        };
    }
}
