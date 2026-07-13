<?php

namespace Modules\Aicem\Database\Seeders;

use App\Shared\Tenancy\Enums\OrganizationStatus;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * spec/Platform_RBAC_Phase2_Specification.md §3.4 (v3.0) — Aicem bắt buộc mọi nội dung phải
 * thuộc đúng 1 Organization thật (workflow/knowledge-base/ngân sách AI đều theo tổ chức, job
 * fail cứng nếu không có). Từ v3.0, `post_articles`/`post_article_translations` không còn
 * `organization_id` nào (Post là tài sản của nền tảng) — nên seed 1 Organization CỐ ĐỊNH
 * riêng cho mục đích này, KHÔNG liên quan gì tới schema Post (không lưu ở bất kỳ cột nào của
 * post_articles). Chỉ dùng làm tenant-context khi Aicem xử lý nội dung Post — xem
 * `PostArticleSubjectResolver::organizationId()`.
 *
 * Tra cứu qua `slug` (bất biến), KHÔNG qua `name` (có thể đổi) — tổ chức `id=1` cũng
 * `is_system=true` nhưng cho mục đích KHÁC hẳn (fallback "chưa xác định/xác thực"), nên
 * KHÔNG dùng chung, phải là 1 record riêng biệt.
 */
class PlatformEditorialOrganizationSeeder extends Seeder
{
    public const SLUG = 'platform-editorial';

    public function run(): void
    {
        Organization::firstOrCreate(
            ['slug' => self::SLUG],
            [
                'uuid'      => (string) Str::uuid(),
                'name'      => 'Vì Gia Đình',
                'status'    => OrganizationStatus::Active,
                'is_system' => true,
            ],
        );

        $this->command?->info('  ✓ Platform editorial organization seeded (slug=' . self::SLUG . ').');
    }
}
