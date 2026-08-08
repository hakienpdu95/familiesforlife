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
            self::Text => 'Đoạn văn bản',
            self::Product => 'Khối sản phẩm',
            self::Faq => 'Câu hỏi thường gặp',
            self::Citation => 'Trích dẫn có nguồn',
            self::Howto => 'Hướng dẫn từng bước',
            self::Comparison => 'Bảng so sánh',
            self::Testimonial => 'Lời chứng thực khách hàng',
        };
    }
}
