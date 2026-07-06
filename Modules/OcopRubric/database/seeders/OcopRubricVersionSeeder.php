<?php

namespace Modules\OcopRubric\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\OcopRubric\Enums\RubricVersionStatus;
use Modules\OcopRubric\Features\RubricAuthoring\Actions\PublishRubricVersionAction;
use Modules\OcopRubric\Features\RubricAuthoring\Actions\UpsertCriterionAction;
use Modules\OcopRubric\Features\RubricAuthoring\Actions\UpsertOptionAction;
use Modules\OcopRubric\Features\RubricAuthoring\Data\CriterionData;
use Modules\OcopRubric\Features\RubricAuthoring\Data\OptionData;
use Modules\OcopRubric\Models\OcopProductGroup;
use Modules\OcopRubric\Models\OcopRubricVersion;

/**
 * Đọc fixture `database/seeders/fixtures/ocop_rubrics.php` (5 Bộ sản phẩm ưu
 * tiên, đã trích xuất + đối chiếu chéo với Phụ lục II) và tạo version 1 (đã
 * publish) cho từng bộ — xem spec §10.
 *
 * Idempotent: nếu 1 Bộ sản phẩm đã có version nào rồi (chạy lại seeder, hoặc
 * system_admin đã tự tạo version qua UI) thì bỏ qua, không tạo trùng.
 */
class OcopRubricVersionSeeder extends Seeder
{
    public function run(): void
    {
        $fixtures = require __DIR__ . '/fixtures/ocop_rubrics.php';
        $adminUserId = DB::table('users')->first()?->id;

        foreach ($fixtures as $fixture) {
            $this->seedProduct($fixture, $adminUserId);
        }
    }

    private function seedProduct(array $fixture, ?int $adminUserId): void
    {
        $group = OcopProductGroup::where('code', $fixture['code'])->first();
        if (!$group) {
            $this->command->warn("  ! Bỏ qua {$fixture['code']} — chưa có OcopProductGroup (chạy OcopProductGroupSeeder trước).");
            return;
        }

        if ($group->rubricVersions()->exists()) {
            $this->command->info("  - {$fixture['code']}: đã có version, bỏ qua.");
            return;
        }

        $version = OcopRubricVersion::create([
            'product_group_id' => $group->id,
            'version_no'       => 1,
            'status'           => RubricVersionStatus::Draft->value,
        ]);

        foreach ($fixture['disqualifiers'] as $index => $label) {
            $version->disqualifiers()->create(['label' => $label, 'sort_order' => $index]);
        }

        $sectionSortOrder = ['A' => 1, 'B' => 2, 'C' => 3];
        foreach ($fixture['sections'] as $sectionData) {
            $section = $version->sections()->create([
                'code'       => $sectionData['code'],
                'label'      => $sectionData['label'],
                'max_score'  => $sectionData['max_score'],
                'sort_order' => $sectionSortOrder[$sectionData['code']] ?? 0,
            ]);

            foreach ($sectionData['criteria'] as $sortIndex => $criterionData) {
                $this->seedCriterion($criterionData, $section->id, null, $sortIndex);
            }
        }

        app(PublishRubricVersionAction::class)->handle($version, $adminUserId ?? 0);

        $this->command->info("  ✓ {$fixture['code']}: đã tạo + publish version 1.");
    }

    private function seedCriterion(array $data, int $sectionId, ?int $parentId, int $sortOrder): void
    {
        $criterion = app(UpsertCriterionAction::class)->handle(new CriterionData(
            rubric_section_id: $sectionId,
            parent_id: $parentId,
            code: $data['code'],
            label: $data['label'],
            max_score: (float) $data['max_score'],
            requirement_note: $data['requirement_note'],
            is_scorable: $data['is_scorable'],
            sort_order: $sortOrder,
        ));

        foreach ($data['options'] as $optIndex => $opt) {
            app(UpsertOptionAction::class)->handle(new OptionData(
                criterion_id: $criterion->id,
                label: $opt['label'],
                points: (float) $opt['points'],
                sort_order: $optIndex,
            ));
        }

        foreach ($data['children'] as $childIndex => $child) {
            $this->seedCriterion($child, $sectionId, $criterion->id, $childIndex);
        }
    }
}
