<?php

namespace Database\Seeders;

use App\Shared\Tenancy\Models\Organization;
use Illuminate\Database\Seeder;

/**
 * Đảm bảo tồn tại đúng 1 tổ chức hệ thống (is_system = true).
 *
 * Mục đích:
 *  - Cung cấp tenant context cho super-admin khi thao tác hệ thống
 *    (upload media, tạo bản ghi, v.v.) mà không thuộc doanh nghiệp nào
 *  - organization_id của các bản ghi "thuộc hệ thống" trỏ về org này
 *  - Không bao giờ bị xóa; không hiển thị trong danh sách doanh nghiệp thông thường
 *
 * Phải chạy TRƯỚC OrganizationSeeder để đảm bảo là bản ghi đầu tiên (id=1 trên DB fresh).
 * Idempotent: chạy lại nhiều lần không tạo duplicate.
 */
class SystemOrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::withoutGlobalScopes()
            ->where('slug', 'system')
            ->first();

        if ($org) {
            // Đảm bảo org đã có đúng is_system = true (trường hợp migrate lại)
            $org->update(['is_system' => true]);
        } else {
            Organization::create([
                'name'      => '#Doanh nghiệp chưa xác định / xác thực#',
                'slug'      => 'system',
                'status'    => 'active',
                'is_system' => true,
                'settings'  => [
                    'timezone' => 'Asia/Ho_Chi_Minh',
                    'locale'   => 'vi',
                ],
            ]);
        }

        // Đảm bảo không có org nào khác bị đánh nhầm is_system = true
        Organization::withoutGlobalScopes()
            ->where('slug', '!=', 'system')
            ->where('is_system', true)
            ->update(['is_system' => false]);
    }
}
