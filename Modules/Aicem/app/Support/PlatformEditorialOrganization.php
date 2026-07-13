<?php

namespace Modules\Aicem\Support;

use App\Shared\Tenancy\Models\Organization;
use Modules\Aicem\Database\Seeders\PlatformEditorialOrganizationSeeder;

/**
 * spec/Platform_RBAC_Phase2_Specification.md §3.4 (v3.0) — resolve ID của Organization cố
 * định dùng làm tenant-context cho Aicem khi xử lý nội dung Post (subject_type=post_article).
 * Tra theo `slug` bất biến (xem PlatformEditorialOrganizationSeeder), memoize trong request để
 * tránh query lặp lại — tổ chức này gần như không bao giờ đổi trong vòng đời 1 request.
 */
final class PlatformEditorialOrganization
{
    private static ?int $id = null;

    public static function id(): int
    {
        if (self::$id !== null) {
            return self::$id;
        }

        $id = Organization::where('slug', PlatformEditorialOrganizationSeeder::SLUG)->value('id');

        if ($id === null) {
            throw new \RuntimeException(
                'Platform editorial organization (slug=' . PlatformEditorialOrganizationSeeder::SLUG . ') chưa được seed — chạy PlatformEditorialOrganizationSeeder trước.',
            );
        }

        return self::$id = $id;
    }
}
