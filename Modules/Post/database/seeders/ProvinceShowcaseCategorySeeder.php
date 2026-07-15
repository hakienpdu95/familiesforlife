<?php

namespace Modules\Post\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Post\Features\CategoryManagement\Actions\CreateCategoryAction;
use Modules\Post\Features\CategoryManagement\Data\CategoryData;
use Modules\Post\Models\PostCategory;

/**
 * spec/Province_Showcase_Technical_Specification.md §3.3 — 2 category con của "du-lich-gia-dinh"
 * (đã có sẵn từ PostDemoSeeder), phục vụ nội dung chuyên đề tỉnh (di sản/ẩm thực) — nội dung
 * chuyên đề tỉnh vẫn nằm trong đúng định vị "gia đình", không tạo category rời rạc ngoài mục
 * đích site. Idempotent — bỏ qua nếu slug đã tồn tại.
 *
 * Chạy SAU Modules\Post\Database\Seeders\PostDemoSeeder (tạo category cha "du-lich-gia-dinh").
 */
class ProvinceShowcaseCategorySeeder extends Seeder
{
    /** @var array<int, array{name: string, icon: string}> */
    private const CATEGORIES = [
        ['name' => 'Di sản văn hóa', 'icon' => 'landmark'],
        ['name' => 'Ẩm thực vùng miền', 'icon' => 'utensils'],
    ];

    public function run(): void
    {
        $parent = PostCategory::where('slug', 'du-lich-gia-dinh')->first();

        if (! $parent) {
            $this->command->warn('  ⚠ Không tìm thấy category cha "du-lich-gia-dinh" — chạy PostDemoSeeder trước.');

            return;
        }

        $creator = \App\Models\User::withoutGlobalScopes()->where('email', 'content-creator@system.local')->first();
        $previousUser = Auth::user();
        if ($creator) {
            Auth::login($creator);
        }

        $created = 0;
        foreach (self::CATEGORIES as $sortOrder => $definition) {
            $slug = Str::slug($definition['name']);

            if (PostCategory::where('slug', $slug)->exists()) {
                continue;
            }

            app(CreateCategoryAction::class)->handle(CategoryData::from([
                'parent_id'  => $parent->id,
                'name'       => $definition['name'],
                'icon'       => $definition['icon'],
                'sort_order' => $sortOrder,
            ]));

            $created++;
        }

        $previousUser ? Auth::login($previousUser) : Auth::logout();

        $this->command->info("  ✓ Province showcase categories seeded ({$created} category mới).");
    }
}
