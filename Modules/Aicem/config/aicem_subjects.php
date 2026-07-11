<?php

// Registry — nguồn sự thật duy nhất cho: (1) field/block nào AI được đụng tới theo subject_type,
// (2) loại tri thức chuyên môn nào tồn tại cho subject_type đó, (3) key nào hợp lệ khi đặt "scope"
// cho 1 knowledge document. Auto-merge vào config('aicem_subjects.*') — xem AicemServiceProvider::boot()
// và spec/AICEM_Technical_Specification.md mục 6.3/6.3.1.
//
// Lưu ý Phase 1: các resolver class dưới đây (Modules\Aicem\Support\Resolvers\*) chưa được cài đặt —
// sẽ hoàn thiện ở Phase 3 (Tích hợp với Post & Product, xem mục 15 Roadmap). Khai báo trước ở đây vì
// registry là nguồn cấu hình tĩnh, không bị lỗi khi class chưa tồn tại (chỉ lỗi khi thực sự instantiate).
return [

    // subject = PostArticleTranslation (không phải PostArticle) từ Publishing Engine Phase 13:
    // title/excerpt/seo_*/blocks giờ per-locale, chỉ tồn tại trên bản dịch cụ thể, không còn
    // trên PostArticle (chỉ còn format/cover/categories/tags dùng chung mọi ngôn ngữ) — xem
    // spec/PublishingEngine_Technical_Specification.md §2/§6. subject_id ở AicemGenerationRun/
    // AicemExampleCandidate cho subject_type=post_article vì vậy là translation_id.
    'post_article' => [
        'model'    => \Modules\Post\Models\PostArticleTranslation::class,
        'resolver' => \Modules\Aicem\Support\Resolvers\PostArticleSubjectResolver::class,
        'label'    => 'Bài viết',
        'fields'   => ['title', 'excerpt', 'seo_title', 'seo_description'],
        'field_constraints' => [
            'title'     => ['max' => 255],
            'seo_title' => ['max' => 60],
        ],
        'has_blocks'           => true,
        'block_editable_types' => ['text'],
        'use_permission'       => 'aicem.use',
        'knowledge_slots' => [
            'eeat_checklist',
            'category_style_guide',
            'seo_keyword_rules',
        ],
        'taxonomy_keys' => ['category_slugs', 'format', 'tag_slugs'],
    ],

    'product' => [
        'model'    => \Modules\Product\Models\Product::class,
        'resolver' => \Modules\Aicem\Support\Resolvers\ProductSubjectResolver::class,
        'label'    => 'Sản phẩm',
        'fields'   => ['name', 'short_description', 'description'],
        'field_constraints' => [
            'name' => ['max' => 250],
        ],
        'has_blocks'     => false,
        'use_permission' => 'aicem.use',
        'knowledge_slots' => [
            'ads_compliance_rules',
            'conversion_copy_rules',
            'pricing_display_rules',
        ],
        'taxonomy_keys' => ['category_slugs', 'price_tier', 'link_types'],
    ],

    // Bảng tra duy nhất cho mọi `type` hợp lệ của aicem_knowledge_documents.type (mục 6.3.1) —
    // dùng bởi Modules\Aicem\Support\KnowledgeSlotRegistry::isValidKnowledgeType().
    //
    // subject_type_required: bắt buộc phải có 1 subject_type non-null khi lưu?
    // subject_type_allowed:  [] = phải là null | [...] = chỉ 1 trong các giá trị này | null = chấp
    //                        nhận bất kỳ subject_type nào đã đăng ký trong registry ở trên.
    'knowledge_slot_definitions' => [
        'skill'                 => ['tier' => 'dna',         'subject_type_required' => false, 'subject_type_allowed' => []],
        'brand_guideline'       => ['tier' => 'dna',         'subject_type_required' => false, 'subject_type_allowed' => []],
        'audience_personas'     => ['tier' => 'dna',         'subject_type_required' => false, 'subject_type_allowed' => []],
        'eeat_checklist'        => ['tier' => 'specialized', 'subject_type_required' => true,  'subject_type_allowed' => ['post_article']],
        'category_style_guide'  => ['tier' => 'specialized', 'subject_type_required' => true,  'subject_type_allowed' => ['post_article']],
        'seo_keyword_rules'     => ['tier' => 'specialized', 'subject_type_required' => true,  'subject_type_allowed' => ['post_article']],
        'ads_compliance_rules'  => ['tier' => 'specialized', 'subject_type_required' => true,  'subject_type_allowed' => ['product']],
        'conversion_copy_rules' => ['tier' => 'specialized', 'subject_type_required' => true,  'subject_type_allowed' => ['product']],
        'pricing_display_rules' => ['tier' => 'specialized', 'subject_type_required' => true,  'subject_type_allowed' => ['product']],
        'example_good'          => ['tier' => 'example',      'subject_type_required' => true,  'subject_type_allowed' => null],
        'example_bad'           => ['tier' => 'example',      'subject_type_required' => true,  'subject_type_allowed' => null],
        'custom_note'           => ['tier' => 'escape_hatch', 'subject_type_required' => false, 'subject_type_allowed' => null],
    ],

];
