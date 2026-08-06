<?php

// spec/N8n_Integration_Technical_Specification.md §3.1 — đầy đủ default + mô tả từng key.
return [

    // Xoá n8n_inbound_logs/n8n_outbound_logs cũ hơn N ngày — chạy bởi PurgeOldN8nLogsAction (§5.7).
    'log_retain_days' => env('N8N_LOG_RETAIN_DAYS', 30),

    // Số ký tự đầu của JSON body được lưu vào payload_excerpt — không lưu nguyên văn (§5.5).
    'log_payload_max_chars' => env('N8N_LOG_PAYLOAD_MAX_CHARS', 2000),

    // Rate limit mặc định cho 1 kết nối KHÔNG tự đặt rate_limit_per_minute riêng (§5.8).
    'default_rate_limit_per_minute' => env('N8N_DEFAULT_RATE_LIMIT', 60),

    // Timeout (giây) khi app gọi RA n8n qua N8nOutboundService::send() (§4).
    'outbound_timeout' => env('N8N_OUTBOUND_TIMEOUT', 10),

    // Số lần thử lại khi gọi RA n8n gặp lỗi mạng/timeout tạm thời (Http::retry(), không tính
    // lỗi 4xx/5xx nghiệp vụ).
    'outbound_max_retries' => env('N8N_OUTBOUND_MAX_RETRIES', 2),

    // Giới hạn kích thước body inbound (byte) — request vượt quá bị từ chối ở bước parse
    // (§5.2 bước 3), TRƯỚC khi đọc hết vào memory.
    'max_inbound_body_size' => env('N8N_MAX_INBOUND_BODY_SIZE', 1_048_576), // 1MB

    // Tên header n8n dùng gửi kèm chữ ký HMAC — đổi được nếu sau này cần tương thích công cụ
    // khác đặt tên header cố định (VD Make/Zapier dùng tên khác) — mặc định theo quy ước riêng
    // platform.
    'signature_header' => env('N8N_SIGNATURE_HEADER', 'X-N8n-Signature'),

];
