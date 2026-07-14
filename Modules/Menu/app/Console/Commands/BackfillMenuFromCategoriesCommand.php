<?php

namespace Modules\Menu\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Menu\Enums\MenuLinkType;
use Modules\Menu\Models\MenuItem;
use Modules\Post\Models\PostCategory;

/**
 * spec/Menu_Navigation_Technical_Specification.md §8.1 — seed menu_items 1:1 với cây
 * PostCategory hiện có + 1 dòng "Sự Kiện". Idempotent: category đã map (đã có menu_items
 * trỏ tới, dù item đó còn active hay đã bị admin sửa) thì BỎ QUA — không update, không tạo
 * trùng. Chạy lại bao nhiêu lần cũng an toàn.
 */
class BackfillMenuFromCategoriesCommand extends Command
{
    protected $signature = 'menu:backfill-from-categories
                            {--user= : ID user dùng làm created_by — mặc định lấy super-admin đầu tiên}
                            {--dry-run : Chỉ in ra sẽ tạo gì, không ghi DB}';

    protected $description = 'Seed menu_items (location=header) từ cây PostCategory hiện có + mục "Sự Kiện" — idempotent, chạy lại an toàn';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $userId = $this->resolveUserId();

        if ($userId === null) {
            $this->error('Không tìm thấy user hợp lệ để gán created_by. Truyền --user=<id> hoặc tạo super-admin trước.');

            return self::FAILURE;
        }

        $created = 0;
        $skipped = 0;

        $rootCategories = PostCategory::active()->root()->with('children')->orderBy('sort_order')->get();

        foreach ($rootCategories as $category) {
            if (MenuItem::where('location', 'header')->where('category_id', $category->id)->exists()) {
                $this->line("Skip: category #{$category->id} ({$category->name}) đã có menu item — bỏ qua.");
                $skipped++;
            } else {
                $this->line("Tạo cấp 1: {$category->name}" . ($dryRun ? ' (dry-run)' : ''));
                $created++;

                $parent = $dryRun ? null : MenuItem::create([
                    'location'    => 'header',
                    'link_type'   => MenuLinkType::Category,
                    'category_id' => $category->id,
                    'label'       => $category->name,
                    'sort_order'  => $category->sort_order,
                    'depth'       => 0,
                    'is_active'   => $category->is_active,
                    'created_by'  => $userId,
                ]);
            }

            foreach ($category->children as $child) {
                if (MenuItem::where('location', 'header')->where('category_id', $child->id)->exists()) {
                    $this->line("  Skip: category #{$child->id} ({$child->name}) đã có menu item — bỏ qua.");
                    $skipped++;

                    continue;
                }

                // Cha vừa bị skip (đã map từ trước) — cần parent_id thật để gắn con vào đúng chỗ,
                // không tạo lại cha. Tra menu item header trỏ category cha đã tồn tại.
                $parentId = $dryRun
                    ? null
                    : ($parent?->id ?? MenuItem::where('location', 'header')->where('category_id', $category->id)->value('id'));

                $this->line("  Tạo cấp 2: {$child->name}" . ($dryRun ? ' (dry-run)' : ''));
                $created++;

                if (! $dryRun) {
                    MenuItem::create([
                        'location'    => 'header',
                        'parent_id'   => $parentId,
                        'link_type'   => MenuLinkType::Category,
                        'category_id' => $child->id,
                        'label'       => $child->name,
                        'sort_order'  => $child->sort_order,
                        'depth'       => 1,
                        'is_active'   => $child->is_active,
                        'created_by'  => $userId,
                    ]);
                }
            }
        }

        $this->backfillEventLink($userId, $dryRun, $created, $skipped);

        $this->newLine();
        $this->info("Hoàn tất — tạo mới: {$created}, bỏ qua (đã map): {$skipped}." . ($dryRun ? ' (dry-run — chưa ghi DB)' : ''));

        return self::SUCCESS;
    }

    /**
     * Mục "Sự Kiện" — trước đây hard-code trong frontend-nav.blade.php (§7.6). Idempotent
     * theo (location, link_type, url) — không dùng category_id nên không tận dụng được
     * unique check ở trên.
     */
    private function backfillEventLink(int $userId, bool $dryRun, int &$created, int &$skipped): void
    {
        $url = route('event.public.home');

        if (MenuItem::where('location', 'header')->where('link_type', MenuLinkType::Url)->where('url', $url)->exists()) {
            $this->line('Skip: mục "Sự Kiện" đã tồn tại — bỏ qua.');
            $skipped++;

            return;
        }

        $this->line('Tạo mục "Sự Kiện"' . ($dryRun ? ' (dry-run)' : ''));
        $created++;

        if (! $dryRun) {
            MenuItem::create([
                'location'   => 'header',
                'link_type'  => MenuLinkType::Url,
                'url'        => $url,
                'label'      => 'Sự Kiện',
                'sort_order' => 999, // sau cùng — đúng vị trí hard-code cũ (luôn ở cuối thanh nav)
                'depth'      => 0,
                'is_active'  => true,
                'created_by' => $userId,
            ]);
        }
    }

    private function resolveUserId(): ?int
    {
        if ($this->option('user')) {
            return (int) $this->option('user');
        }

        return User::withoutGlobalScopes()->role('super-admin')->orderBy('id')->value('id');
    }
}
