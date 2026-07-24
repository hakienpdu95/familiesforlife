<?php

namespace Modules\Post\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostAuthorProfile;

/**
 * Demo Author Hub — dựng dữ liệu để xem thử byline link (public/article.blade.php) + card
 * "Hồ sơ tác giả công khai" ở /auth/profile + trang /tac-gia, /tac-gia/{slug}.
 *
 * KHÔNG tạo bài viết mới — gán lại `created_by` của các bài viết PUBLISHED sẵn có (seed bởi
 * PostDemoSeeder) cho vài tài khoản platform demo đã có sẵn (chia đều), tránh phình CSDL bằng
 * dữ liệu giả tạo thêm (đúng nguyên tắc "dùng Google Analytics cho thống kê, không tự đếm").
 *
 * Chạy: php artisan db:seed --class="Modules\Post\Database\Seeders\AuthorHubDemoSeeder"
 */
class AuthorHubDemoSeeder extends Seeder
{
    /** email user demo (đã có sẵn từ seeder platform user) => hồ sơ tác giả muốn gán. */
    private const PROFILES = [
        'content-creator@system.local' => [
            'pen_name'     => 'Minh Quân',
            'bio'          => 'Phóng viên mảng đời sống - xã hội, chuyên viết tin tức gia đình và giáo dục con cái.',
            'social_links' => ['facebook' => 'https://facebook.com/minhquan.reporter'],
        ],
        'editor@system.local' => [
            'pen_name'     => 'Thu Hà',
            'bio'          => 'Biên tập viên — duyệt sơ bộ bài viết trước khi chuyển lãnh đạo nội dung xuất bản.',
            'social_links' => ['facebook' => 'https://facebook.com/thuha.editor', 'x' => 'https://x.com/thuha_editor'],
        ],
        'content-head@system.local' => [
            'pen_name'     => 'Trần Bảo Nam',
            'bio'          => 'Trưởng phòng nội dung nền tảng — phụ trách duyệt cuối và xuất bản bài viết.',
            'social_links' => ['linkedin' => 'https://linkedin.com/in/tranbaonam'],
        ],
        'section-editor@system.local' => [
            'pen_name'     => 'Lê Phương Anh',
            'bio'          => 'Biên tập viên trưởng chuyên mục Sức khỏe & Dinh dưỡng.',
            'social_links' => ['website' => 'https://lephuonganh.vn'],
        ],
    ];

    public function run(): void
    {
        $users = User::whereIn('email', array_keys(self::PROFILES))->get()->keyBy('email');

        if ($users->isEmpty()) {
            $this->command->warn('  ⚠ Không tìm thấy user demo nào (chạy PostReviewDemoSeeder/platform user seeder trước) — bỏ qua AuthorHubDemoSeeder.');

            return;
        }

        $this->redistributePublishedArticles($users->values());

        foreach ($users as $email => $user) {
            $data = self::PROFILES[$email];

            PostAuthorProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'slug'         => PostAuthorProfile::slugFor($user, $data['pen_name']),
                    'pen_name'     => $data['pen_name'],
                    'bio'          => $data['bio'],
                    'social_links' => $data['social_links'],
                    'is_public'    => true,
                ]
            );
        }

        $this->command->info("  ✓ AuthorHub demo: {$users->count()} hồ sơ tác giả + gán lại created_by cho bài viết published sẵn có.");
    }

    /** Chia đều bài viết published sẵn có cho các user demo — không tạo bài mới. */
    private function redistributePublishedArticles(\Illuminate\Support\Collection $users): void
    {
        $articleIds = PostArticle::query()
            ->whereHas('translations', fn ($q) => $q->published())
            ->pluck('id')
            ->all();

        if (empty($articleIds) || $users->isEmpty()) {
            return;
        }

        $chunks = array_chunk($articleIds, (int) ceil(count($articleIds) / $users->count()));

        foreach ($users as $index => $user) {
            if (isset($chunks[$index])) {
                PostArticle::whereIn('id', $chunks[$index])->update(['created_by' => $user->id]);
            }
        }
    }
}
