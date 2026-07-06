<?php

/**
 * sanitize_rich_text()
 *
 * Làm sạch HTML output từ Jodit trước khi lưu DB / hiển thị giao diện.
 * Chỉ giữ lại các tag định dạng an toàn, loại bỏ event handlers và
 * javascript: URI để ngăn stored XSS.
 *
 * Dùng trong Actions trước khi persist:
 *   $data['description'] = sanitize_rich_text($data['description']);
 *
 * Hoặc trong Blade khi render:
 *   {!! sanitize_rich_text($model->description) !!}
 */
function sanitize_rich_text(?string $html): ?string
{
    if (blank($html)) {
        return $html;
    }

    // Các tag được phép giữ lại (output từ Jodit compact/standard/full)
    $allowedTags = implode('', [
        '<p>', '<br>', '<b>', '<i>', '<u>', '<s>',
        '<strong>', '<em>',
        '<ul>', '<ol>', '<li>',
        '<h1>', '<h2>', '<h3>', '<h4>', '<h5>', '<h6>',
        '<blockquote>', '<hr>',
        '<a>', '<span>',
    ]);

    $clean = strip_tags($html, $allowedTags);

    // Xóa inline event handlers (onclick, onmouseover, onerror, ...)
    $clean = preg_replace('/\s+on[a-z]+\s*=\s*"[^"]*"/i', '', $clean);
    $clean = preg_replace("/\s+on[a-z]+\s*=\s*'[^']*'/i", '', $clean);
    $clean = preg_replace('/\s+on[a-z]+\s*=\s*[^\s>]*/i', '', $clean);

    // Vô hiệu hóa javascript: URI trong href / src
    $clean = preg_replace('/href\s*=\s*(["\'])\s*javascript:[^"\']*\1/i', 'href="#"', $clean);
    $clean = preg_replace("/href\s*=\s*javascript:[^\s>]*/i", 'href="#"', $clean);

    return $clean;
}
