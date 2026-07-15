<?php

namespace Modules\ProvinceShowcase\Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * spec/Province_Showcase_Technical_Specification.md §3.1 — backfill slug cho TOÀN BỘ tỉnh hiện
 * có (kể cả tỉnh chưa "chuyên đề hóa"), bằng Str::slug($province->name). Chạy như 1 Seeder
 * (không phải migration) vì phụ thuộc dữ liệu provinces đã được import
 * (php artisan import:provinces-wards) — trên fresh DB, provinces rỗng tại thời điểm chạy
 * migration, backfill lúc đó sẽ không có gì để cập nhật.
 *
 * Idempotent — chỉ backfill tỉnh còn slug=null, không ghi đè slug đã có.
 */
class ProvinceSlugBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = Province::whereNull('slug')->get();

        foreach ($provinces as $province) {
            $province->update(['slug' => $this->uniqueSlug($province->name, $province->id)]);
        }

        $this->command->info("  ✓ Province slug backfilled ({$provinces->count()} tỉnh/thành).");
    }

    /**
     * "Thành phố Trung Ương" (Hà Nội, Hải Phòng, Huế, Đà Nẵng, HCM, Cần Thơ) mang tiền tố
     * "Thành phố " ngay trong cột `name` (dữ liệu chính thức từ datafiles/provinces.json — vd
     * "Thành phố Huế"), trong khi "Tỉnh" KHÔNG có tiền tố "Tỉnh " tương tự (vd "Cà Mau"). Bỏ
     * tiền tố này khi tạo slug để có URL gọn (/thanh-pho/hue thay vì /thanh-pho/thanh-pho-hue)
     * — place_type đã tự nói rõ đây là thành phố trực thuộc trung ương qua chính route {type}.
     */
    private function uniqueSlug(string $name, int $ignoreId): string
    {
        $base = Str::slug(preg_replace('/^Thành phố\s+/u', '', $name));
        $slug = $base;
        $i    = 2;

        while (Province::where('slug', $slug)->where('id', '!=', $ignoreId)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
