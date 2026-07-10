<?php

namespace Modules\Aicem\Database\Seeders;

use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Modules\Aicem\Models\AicemContextTemplate;
use Modules\Aicem\Models\AicemWorkflow;

/**
 * Seed 3 workflow cố định "headline" + "seo_audit" + "full_optimization" cho
 * subject_type=post_article — phạm vi đã chọn ở Phase 3: chỉ cần các workflow này CHẠY ĐƯỢC
 * (spec/AICEM_Technical_Specification.md mục 15), CHƯA làm CRUD UI cho ContextTemplate/Workflow
 * (AI_Operator tự tạo template/workflow riêng là phần mở rộng sau). full_optimization thêm ở
 * Phase 4 ("workflow đa-block") — duy nhất workflow có section subject_blocks, sinh suggestion
 * trải trên NHIỀU block cùng lúc trong 1 lần chạy. Idempotent theo (organization_id, subject_type,
 * slug) — chạy lại không tạo trùng, an toàn khi seed nhiều Organization.
 */
class AicemDefaultWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        Organization::query()->each(function (Organization $organization) {
            TenantContext::runForOrganization($organization, function () {
                $this->seedHeadlineWorkflow();
                $this->seedSeoAuditWorkflow();
                $this->seedFullOptimizationWorkflow();
            });
        });

        $this->command?->info('  ✓ Aicem default workflows (headline, seo_audit, full_optimization) seeded.');
    }

    private function seedHeadlineWorkflow(): void
    {
        $template = AicemContextTemplate::updateOrCreate(
            ['subject_type' => 'post_article', 'slug' => 'headline-default'],
            [
                'name'       => 'Tối ưu tiêu đề — mặc định',
                'version'    => 1,
                'is_default' => true,
                'schema'     => [
                    'subject_type' => 'post_article',
                    'sections'     => [
                        ['source' => 'knowledge_document', 'type' => 'skill'],
                        ['source' => 'knowledge_document', 'type' => 'brand_guideline'],
                        ['source' => 'knowledge_document', 'type' => 'audience_personas'],
                        ['source' => 'knowledge_document', 'type' => 'seo_keyword_rules'],
                        ['source' => 'subject_taxonomy'],
                        ['source' => 'subject_fields', 'fields' => ['title']],
                    ],
                    'output_contract' => $this->defaultOutputContract(),
                ],
            ]
        );

        AicemWorkflow::updateOrCreate(
            ['subject_type' => 'post_article', 'slug' => 'headline'],
            [
                'name'                => 'Tối ưu tiêu đề',
                'prompt_template'     => 'Đề xuất 1 phương án tiêu đề (title) mới hấp dẫn hơn và tối ưu SEO '
                    . 'hơn, giữ nguyên ý nghĩa, cho bài viết có tiêu đề hiện tại: "{{title}}". '
                    . 'Trả về đúng 1 suggestion cho field="title".',
                'filters'             => null,
                'context_template_id' => $template->id,
                'is_active'           => true,
            ]
        );
    }

    private function seedSeoAuditWorkflow(): void
    {
        $template = AicemContextTemplate::updateOrCreate(
            ['subject_type' => 'post_article', 'slug' => 'seo-audit-default'],
            [
                'name'       => 'SEO Audit — mặc định',
                'version'    => 1,
                'is_default' => true,
                'schema'     => [
                    'subject_type' => 'post_article',
                    'sections'     => [
                        ['source' => 'knowledge_document', 'type' => 'skill'],
                        ['source' => 'knowledge_document', 'type' => 'brand_guideline'],
                        ['source' => 'knowledge_document', 'type' => 'eeat_checklist'],
                        ['source' => 'knowledge_document', 'type' => 'seo_keyword_rules'],
                        ['source' => 'subject_taxonomy'],
                        ['source' => 'subject_fields', 'fields' => ['excerpt', 'seo_title', 'seo_description']],
                    ],
                    'output_contract' => $this->defaultOutputContract(),
                ],
            ]
        );

        AicemWorkflow::updateOrCreate(
            ['subject_type' => 'post_article', 'slug' => 'seo_audit'],
            [
                'name'                => 'SEO Audit',
                'prompt_template'     => 'Audit SEO cho bài viết: kiểm tra excerpt, seo_title (tối đa 60 ký tự) '
                    . 'và seo_description (tối đa 160 ký tự) hiện tại. Excerpt hiện tại: "{{excerpt}}". '
                    . 'SEO title hiện tại: "{{seo_title}}". SEO description hiện tại: "{{seo_description}}". '
                    . 'Chỉ đề xuất field nào thực sự cần cải thiện, không đề xuất field đã tốt.',
                'filters'             => null,
                'context_template_id' => $template->id,
                'is_active'           => true,
            ]
        );
    }

    private function seedFullOptimizationWorkflow(): void
    {
        $template = AicemContextTemplate::updateOrCreate(
            ['subject_type' => 'post_article', 'slug' => 'full-optimization-default'],
            [
                'name'       => 'Tối ưu toàn bài — mặc định',
                'version'    => 1,
                'is_default' => true,
                'schema'     => [
                    'subject_type' => 'post_article',
                    'sections'     => [
                        ['source' => 'knowledge_document', 'type' => 'skill'],
                        ['source' => 'knowledge_document', 'type' => 'brand_guideline'],
                        ['source' => 'knowledge_document', 'type' => 'audience_personas'],
                        ['source' => 'knowledge_document', 'type' => 'eeat_checklist'],
                        ['source' => 'knowledge_document', 'type' => 'category_style_guide'],
                        ['source' => 'knowledge_document', 'type' => 'seo_keyword_rules'],
                        ['source' => 'knowledge_document', 'type' => 'example_good', 'subject_type' => 'post_article', 'limit' => 2],
                        ['source' => 'subject_taxonomy'],
                        ['source' => 'subject_fields', 'fields' => ['title', 'excerpt', 'seo_title', 'seo_description']],
                        [
                            'source'       => 'subject_blocks',
                            'block_types'  => ['text'],
                            'instruction'  => 'Tối ưu văn phong, giữ nguyên nghĩa, không đụng block product.',
                        ],
                    ],
                    'output_contract' => $this->defaultOutputContract(),
                ],
            ]
        );

        AicemWorkflow::updateOrCreate(
            ['subject_type' => 'post_article', 'slug' => 'full_optimization'],
            [
                'name'                => 'Tối ưu toàn bài',
                'prompt_template'     => 'Tối ưu toàn bộ nội dung bài viết: tiêu đề, mô tả ngắn, SEO title/description, '
                    . 'và từng đoạn văn bản (block) — giữ nguyên ý nghĩa, chỉ cải thiện văn phong/rõ ràng/SEO. '
                    . 'KHÔNG đề xuất cho block sản phẩm. Với mỗi field/block cần cải thiện, trả về đúng 1 suggestion '
                    . '(field hoặc block_id tương ứng); field/block đã tốt thì không cần đề xuất.',
                'filters'             => null,
                'context_template_id' => $template->id,
                'is_active'           => true,
            ]
        );
    }

    private function defaultOutputContract(): array
    {
        return [
            'type'       => 'suggestions_array',
            'item_shape' => [
                'field'          => 'string|null',
                'block_id'       => 'int|null',
                'suggested_text' => 'string',
                'reason'         => 'string',
            ],
        ];
    }
}
