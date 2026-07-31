<?php

namespace Modules\Video\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Video\Models\Video;

/**
 * spec/Video_Management_Technical_Specification.md §8 — vài video mẫu để QA ngay được cả trang
 * công khai lẫn thumbnail/lightbox mà không cần tự tay tìm URL YouTube. Dùng ID video công khai
 * lâu năm, không đại diện/liên quan tổ chức nào trong dự án (cùng tinh thần tránh dùng tài sản
 * của 1 bên thứ ba ngụ ý liên kết — xem Banner_Management_Technical_Specification.md §0).
 *
 * Chạy thủ công: php artisan db:seed --class="Modules\Video\Database\Seeders\VideoDemoSeeder"
 * KHÔNG tự động trong SystemDataSeeder — cùng lý do OrganizationDemoSeeder/PostReviewDemoSeeder
 * không tự chạy.
 */
class VideoDemoSeeder extends Seeder
{
    private const VIDEOS = [
        [
            'name'             => 'Me at the zoo',
            'description'      => 'Video đầu tiên từng đăng lên YouTube — dùng làm dữ liệu demo để QA thumbnail/lightbox.',
            'youtube_video_id' => 'jNQXAC9IVRw',
        ],
        [
            'name'             => 'Video demo QA #2',
            'description'      => 'Video công khai lâu năm, dùng để kiểm tra hiển thị lưới/lightbox trang /videos.',
            'youtube_video_id' => 'dQw4w9WgXcQ',
        ],
        [
            'name'             => 'Big Buck Bunny (trailer)',
            'description'      => 'Phim hoạt hình mã nguồn mở của Blender Foundation — an toàn về bản quyền, dùng làm demo.',
            'youtube_video_id' => 'aqz-KE-bpKQ',
        ],
    ];

    public function run(): void
    {
        $creator = User::withoutGlobalScopes()->where('email', 'ops@system.local')->first();

        if (! $creator) {
            $this->command->warn('  ⚠ Thiếu tài khoản platform (ops@system.local) — chạy ApprovalDatabaseSeeder trước.');

            return;
        }

        foreach (self::VIDEOS as $index => $definition) {
            Video::updateOrCreate(
                ['youtube_video_id' => $definition['youtube_video_id']],
                [
                    'name'        => $definition['name'],
                    'description' => $definition['description'],
                    'video_url'   => "https://www.youtube.com/watch?v={$definition['youtube_video_id']}",
                    'embed_code'  => "https://www.youtube.com/watch?v={$definition['youtube_video_id']}",
                    'sort_order'  => $index,
                    'is_active'   => true,
                    'created_by'  => $creator->id,
                ]
            );
        }

        $this->command->info('  ✓ Video demo seeded — 3 video mẫu (xem /videos).');
    }
}
