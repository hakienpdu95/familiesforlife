<?php

namespace Modules\Video\Features\VideoManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;

/**
 * spec/Video_Management_Technical_Specification.md §4.3 — trái tim của việc chống XSS/phishing
 * của toàn bộ module. Action này CHỈ đọc chuỗi để tìm ID theo regex whitelist — không bao giờ
 * echo/replace lại $input, không strip_tags rồi render phần còn lại. Nếu không khớp bất kỳ
 * pattern nào, coi như không hợp lệ, không cố "cứu" bằng cách nới lỏng regex.
 */
class ResolveYoutubeVideoIdAction
{
    use AsAction;

    private const ID_PATTERN = '[A-Za-z0-9_-]{11}';

    /**
     * Thử trích xuất ID hợp lệ (11 ký tự) từ $embedCode trước (chấp nhận nguyên khối <iframe>,
     * URL watch/shorts/live/embed, hoặc ID trần), rồi fallback sang $videoUrl nếu $embedCode
     * không trích được gì (hoặc rỗng — cả 2 tham số đều nullable, §0/§5.1). Trả null nếu CẢ HAI
     * đều không có ID hợp lệ — nơi gọi (Create/UpdateVideoAction) chịu trách nhiệm quăng lỗi
     * validate, hàm này chỉ resolve thuần tuý, không side-effect.
     */
    public function handle(?string $embedCode, ?string $videoUrl = null): ?string
    {
        if ($embedCode && $id = $this->extractFrom($embedCode)) {
            return $id;
        }

        return $videoUrl ? $this->extractFrom($videoUrl) : null;
    }

    /**
     * Dùng bởi Action Create/Update để validate video_url trước khi lưu — so khớp host CHÍNH
     * XÁC qua parse_url(), không phải tìm chuỗi con trong URL. Đọc `config('video.allowed_hosts')`
     * — CÙNG NGUỒN với `Video::getWatchUrlAttribute()`, sửa 1 chỗ duy nhất trong config/video.php
     * nếu cần thêm/bớt domain.
     */
    public function isWhitelistedHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host !== null && in_array($host, config('video.allowed_hosts', []), true);
    }

    private function extractFrom(string $input): ?string
    {
        $input = trim($input);

        // (?<![=\w]) — bắt buộc ký tự NGAY TRƯỚC "https?://" (nếu có) không phải "=" hay ký tự
        // chữ/số/gạch dưới. Không có lookbehind này, preg_match (tìm khớp Ở BẤT KỲ ĐÂU trong
        // chuỗi) sẽ bị qua mặt bởi domain giả mạo kiểu
        // "https://evil.com/?redirect=https://youtube.com/watch?v=ID" — URL YouTube "thật" nằm
        // lồng bên trong 1 URL khác vẫn khớp regex dù host THẬT của toàn bộ chuỗi là evil.com.
        // Ký tự đứng ngay trước URL lồng đó luôn là "=" (dấu gán giá trị query param) trong mọi
        // biến thể tấn công kiểu này — chặn đúng điểm đó là đủ, không cần parse URL đầy đủ ở
        // tầng regex. Không áp dụng cho pattern <iframe> (đã tự anchor qua src=["'] ngay trước
        // domain, không có khoảng trống để chèn "=" hay ký tự khác vào giữa).
        $notEmbedded = '(?<![=\w])';

        $patterns = [
            // <iframe ... src="https://www.youtube.com/embed/ID" ...> (thứ tự attribute/loại
            // quote bất kỳ nhờ [^>]+ quét cả thẻ)
            '~<iframe[^>]+src=["\']https?://(?:www\.)?youtube(?:-nocookie)?\.com/embed/(' . self::ID_PATTERN . ')~i',
            // https://www.youtube.com/watch?v=ID (v là param ĐẦU) hoặc watch?list=...&v=ID (v
            // đứng sau param khác) — (?:[^"'\s]*&)? bắt buộc "v=" phải đứng ngay sau "?" HOẶC
            // ngay sau "&", không phải match nhầm 1 param khác chứa chuỗi con "v=" (vd
            // "?abv=xxxxxxxxxxx" không có "&" trước "v=" nên KHÔNG khớp). Bug đã sửa: bản trước
            // yêu cầu "[?&]" xuất hiện lại sau dấu "?" gốc — luôn fail khi v= là param đầu tiên
            // (trường hợp watch?v=... phổ biến nhất) vì dấu "?" đã bị literal "\?" tiêu thụ.
            '~' . $notEmbedded . 'https?://(?:www\.|m\.|music\.)?youtube\.com/watch\?(?:[^"\'\s]*&)?v=(' . self::ID_PATTERN . ')~i',
            // https://youtu.be/ID (kèm hoặc không kèm ?si=.../timestamp — capture group cố định
            // 11 ký tự nên không ăn lẫn query phía sau)
            '~' . $notEmbedded . 'https?://youtu\.be/(' . self::ID_PATTERN . ')~i',
            // https://www.youtube.com/shorts/ID
            '~' . $notEmbedded . 'https?://(?:www\.)?youtube\.com/shorts/(' . self::ID_PATTERN . ')~i',
            // https://www.youtube.com/live/ID
            '~' . $notEmbedded . 'https?://(?:www\.)?youtube\.com/live/(' . self::ID_PATTERN . ')~i',
            // https://www.youtube.com/v/ID (legacy player URL)
            '~' . $notEmbedded . 'https?://(?:www\.)?youtube\.com/v/(' . self::ID_PATTERN . ')~i',
            // https://www.youtube.com/embed/ID hoặc youtube-nocookie.com/embed/ID (dán thẳng URL
            // embed, không kèm thẻ iframe)
            '~' . $notEmbedded . 'https?://(?:www\.|m\.)?youtube(?:-nocookie)?\.com/embed/(' . self::ID_PATTERN . ')~i',
            // ID trần (người dùng chỉ dán đúng 11 ký tự, không kèm URL)
            '~^(' . self::ID_PATTERN . ')$~',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input, $matches) === 1) {
                return $matches[1];
            }
        }

        return null;
    }
}
