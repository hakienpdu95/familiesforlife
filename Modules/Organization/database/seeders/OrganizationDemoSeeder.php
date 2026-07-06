<?php

namespace Modules\Organization\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationDemoSeeder extends Seeder
{
    private const COUNT = 1000;

    private const PREFIXES = [
        'Công ty TNHH', 'Công ty Cổ phần', 'Công ty TNHH MTV',
        'Công ty Hợp danh', 'Tập đoàn', 'Tổng Công ty',
    ];

    private const KEYWORDS = [
        'Phát triển', 'Đầu tư', 'Thương mại', 'Dịch vụ', 'Sản xuất',
        'Xuất Nhập Khẩu', 'Giải Pháp', 'Công Nghệ', 'Quốc Tế', 'Việt Nam',
        'Tư Vấn', 'Phân Phối', 'Kinh Doanh', 'Xây Dựng', 'Bền Vững',
        'Toàn Cầu', 'Liên Doanh', 'Hợp Tác', 'Tổng Hợp', 'Kỹ Thuật',
    ];

    private const INDUSTRIES = [
        'Công nghệ thông tin', 'Thương mại điện tử', 'Xây dựng', 'Bất động sản',
        'Sản xuất công nghiệp', 'Logistics & Vận tải', 'Tài chính - Ngân hàng',
        'Y tế & Dược phẩm', 'Giáo dục & Đào tạo', 'Nông nghiệp & Thực phẩm',
        'Du lịch & Khách sạn', 'Bán lẻ', 'Năng lượng & Điện lực', 'Môi trường',
        'Truyền thông & Marketing', 'Tư vấn & Dịch vụ', 'Dệt may & Thời trang',
        'Thủy sản & Chế biến', 'Khai khoáng', 'Hóa chất & Vật liệu',
    ];

    private const CITIES = [
        'Hà Nội', 'Hồ Chí Minh', 'Đà Nẵng', 'Cần Thơ', 'Hải Phòng',
        'Biên Hòa', 'Bình Dương', 'Huế', 'Nha Trang', 'Vũng Tàu',
        'Nam Định', 'Thái Nguyên', 'Vinh', 'Đà Lạt', 'Quy Nhơn',
    ];

    private const STREETS = [
        'Nguyễn Huệ', 'Lê Lợi', 'Trần Phú', 'Đinh Tiên Hoàng', 'Nguyễn Trãi',
        'Hoàng Hoa Thám', 'Cách Mạng Tháng 8', 'Võ Văn Kiệt', 'Phạm Văn Đồng',
        'Lý Thường Kiệt', 'Hai Bà Trưng', 'Trường Chinh', 'Nguyễn Văn Cừ',
        'Bà Triệu', 'Điện Biên Phủ', 'Ngô Quyền', 'Phan Chu Trinh',
    ];

    private const PHONE_PREFIXES = [
        '090', '091', '093', '096', '097', '098',
        '032', '033', '034', '035', '036', '086', '089',
    ];

    // 80% active, 10% suspended, 10% inactive — weighted pool
    private const STATUSES = [
        'active', 'active', 'active', 'active', 'active',
        'active', 'active', 'active', 'suspended', 'inactive',
    ];

    private const MEMBER_ROLES = ['owner', 'admin', 'manager', 'member', 'member', 'member'];

    public function run(): void
    {
        if (DB::table('organizations')->count() >= self::COUNT + 2) {
            $this->command->warn('Demo data already exists. Skipping.');
            return;
        }

        $provinces      = DB::table('provinces')->pluck('province_code')->toArray();
        $wardsByProvince = [];
        DB::table('wards')->select('ward_code', 'province_code')->orderBy('id')->get()
            ->each(fn($w) => $wardsByProvince[$w->province_code][] = $w->ward_code);

        $userIds     = DB::table('users')->pluck('id')->toArray();
        $existingSlugs = DB::table('organizations')->pluck('slug')->flip()->toArray();

        $now  = Carbon::now();
        $orgs = [];

        for ($i = 1; $i <= self::COUNT; $i++) {
            [$name, $slug] = $this->makeName($i, $existingSlugs);

            $provinceCode = $this->pick($provinces);
            $wards        = $wardsByProvince[$provinceCode] ?? [];
            $wardCode     = $wards ? $this->pick($wards) : null;

            $createdAt = $now->copy()->subDays(mt_rand(0, 730))->subHours(mt_rand(0, 23));

            $orgs[] = [
                'uuid'          => Str::uuid()->toString(),
                'name'          => $name,
                'slug'          => $slug,
                'status'        => self::STATUSES[$i % 10],
                'tax_code'      => $this->randomTaxCode(),
                'phone'         => $this->randomPhone(),
                'email'         => 'info@' . $slug . '.com',
                'website'       => 'https://www.' . $slug . '.vn',
                'industry'      => $this->pick(self::INDUSTRIES),
                'address'       => 'Số ' . mt_rand(1, 500) . ' ' . $this->pick(self::STREETS),
                'city'          => $this->pick(self::CITIES),
                'country'       => 'VN',
                'province_code' => $provinceCode,
                'ward_code'     => $wardCode,
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt->copy()->addDays(mt_rand(0, 30)),
            ];
        }

        foreach (array_chunk($orgs, 100) as $chunk) {
            DB::table('organizations')->insert($chunk);
        }

        $this->command->info('Inserted 1000 demo organizations.');

        if (!empty($userIds)) {
            $inserted = $this->seedMembers($userIds, $now);
            $this->command->info("Inserted {$inserted} demo member rows.");
        }
    }

    private function makeName(int $i, array &$existingSlugs): array
    {
        $prefix   = $this->pick(self::PREFIXES);
        $keyword  = $this->pick(self::KEYWORDS);
        $industry = $this->pick(self::INDUSTRIES);
        $city     = $this->pick(self::CITIES);

        $name = match ($i % 5) {
            0 => "{$prefix} {$keyword} {$city}",
            1 => "{$prefix} {$keyword} & {$industry}",
            2 => "{$prefix} {$city} {$keyword}",
            3 => "{$prefix} {$industry} {$keyword}",
            4 => "{$prefix} {$keyword} {$city} {$i}",
        };

        $baseSlug = Str::slug($name);
        $slug     = $baseSlug;
        $counter  = 2;
        while (isset($existingSlugs[$slug])) {
            $slug = $baseSlug . '-' . $counter++;
        }
        $existingSlugs[$slug] = true;

        return [$name, $slug];
    }

    private function seedMembers(array $userIds, Carbon $now): int
    {
        $orgIds  = DB::table('organizations')->whereNull('owner_id')->pluck('id')->toArray();
        $maxPer  = min(count($userIds), 7);
        $rows    = [];
        $total   = 0;

        foreach ($orgIds as $orgId) {
            $count  = mt_rand(1, $maxPer);
            $picked = $this->sampleWithoutReplacement($userIds, $count);

            foreach ($picked as $userId) {
                $rows[] = [
                    'organization_id' => $orgId,
                    'user_id'         => $userId,
                    'role'            => $this->pick(self::MEMBER_ROLES),
                    'joined_at'       => $now->copy()->subDays(mt_rand(0, 365)),
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }

            if (count($rows) >= 500) {
                DB::table('organization_members')->insertOrIgnore($rows);
                $total += count($rows);
                $rows   = [];
            }
        }

        if (!empty($rows)) {
            DB::table('organization_members')->insertOrIgnore($rows);
            $total += count($rows);
        }

        return $total;
    }

    private function sampleWithoutReplacement(array $arr, int $n): array
    {
        shuffle($arr);
        return array_slice($arr, 0, min($n, count($arr)));
    }

    private function pick(array $arr): mixed
    {
        return $arr[array_rand($arr)];
    }

    private function randomTaxCode(): string
    {
        // Vietnamese tax code: 10 digits (company) or 13 digits (branch)
        return mt_rand(0, 1)
            ? str_pad((string) mt_rand(1000000000, 9999999999), 10, '0')
            : str_pad((string) mt_rand(1000000000, 9999999999), 10, '0') . '-' . str_pad((string) mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    }

    private function randomPhone(): string
    {
        return $this->pick(self::PHONE_PREFIXES) . str_pad((string) mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
    }
}
