<?php

namespace Modules\ProvinceShowcase\Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\ProvinceShowcase\Support\ProvinceTabColor;

/**
 * Chạy: php artisan db:seed --class="Modules\ProvinceShowcase\Database\Seeders\ProvinceFeaturedSeeder"
 */
class ProvinceFeaturedSeeder extends Seeder
{
    private const THEME_COLORS = [
        'ha-noi' => '#ea1d76',
        'cao-bang' => null,
        'tuyen-quang' => null,
        'dien-bien' => null,
        'lai-chau' => null,
        'son-la' => null,
        'lao-cai' => null,
        'thai-nguyen' => null,
        'lang-son' => null,
        'quang-ninh' => '#304497',
        'bac-ninh' => null,
        'phu-tho' => null,
        'hai-phong' => '#eab02d',
        'hung-yen' => null,
        'ninh-binh' => '#64d89e',
        'thanh-hoa' => null,
        'nghe-an' => null,
        'ha-tinh' => null,
        'quang-tri' => null,
        'hue' => '#7a1f2b',
        'da-nang' => null,
        'quang-ngai' => null,
        'gia-lai' => null,
        'khanh-hoa' => null,
        'dak-lak' => null,
        'lam-dong' => null,
        'dong-nai' => null,
        'ho-chi-minh' => null,
        'tay-ninh' => null,
        'dong-thap' => null,
        'vinh-long' => null,
        'an-giang' => null,
        'can-tho' => null,
        'ca-mau' => null,
    ];

    private const FEATURED = [
        'ninh-binh',
        'quang-ninh',
        'ha-noi',
        'hai-phong',
        'hue',
    ];

    public function run(): void
    {
        $featured = array_slice(self::FEATURED, 0, (int) config('provinceshowcase.featured_max', 5));

        DB::transaction(function () use ($featured) {
            Province::query()
                ->whereNotIn('slug', array_keys(self::THEME_COLORS))
                ->update(['theme_color' => ProvinceTabColor::default()]);

            foreach (self::THEME_COLORS as $slug => $hex) {
                Province::where('slug', $slug)->update(['theme_color' => ProvinceTabColor::resolve($hex)]);
            }

            Province::where('is_featured', true)->whereNotIn('slug', $featured)->update(['is_featured' => false]);

            foreach ($featured as $index => $slug) {
                Province::where('slug', $slug)->update(['is_featured' => true, 'order_column' => $index + 1]);
            }
        });

        $this->command?->info('  ✓ Theme colors for all provinces + '.count($featured).' featured provinces seeded.');
    }
}
