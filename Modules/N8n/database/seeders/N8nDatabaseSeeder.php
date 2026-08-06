<?php

namespace Modules\N8n\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * spec/N8n_Integration_Technical_Specification.md §6 — module này KHÔNG có permission Spatie
 * riêng để seed: gate hoàn toàn bằng Platform Roles đã có sẵn (platform_ops/platform_viewer/
 * super-admin — app/Models/User.php), không phải role/permission module tự khai báo mới. Giữ
 * file rỗng để `module:seed n8n` (nếu gọi) không lỗi.
 */
class N8nDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Không có gì để seed — xem docblock lớp.
    }
}
