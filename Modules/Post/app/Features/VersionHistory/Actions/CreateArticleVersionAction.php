<?php

namespace Modules\Post\Features\VersionHistory\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\ContentBlockType;
use Modules\Post\Enums\VersionTrigger;
use Modules\Post\Jobs\PersistArticleVersionJob;
use Modules\Post\Models\PostArticleTranslation;

/**
 * spec/Post_VersionHistory_Technical_Specification.md §9.2 — đóng gói snapshot NGAY (đồng bộ,
 * đọc dữ liệu vừa ghi trong transaction hiện tại) rồi dispatch job ghi DB sau khi transaction
 * hiện tại commit (§9.1 — tránh race điều kiện chụp nhầm nội dung của lần lưu sau).
 */
class CreateArticleVersionAction
{
    use AsAction;

    public function handle(
        PostArticleTranslation $translation,
        VersionTrigger $trigger,
        ?int $userId,
        ?int $restoredFromVersionId = null, // chỉ truyền khi $trigger === Restore (§9.5, §18.1)
    ): void {
        $snapshot = $this->buildSnapshot($translation);

        DB::afterCommit(
            fn () => PersistArticleVersionJob::dispatch(
                $translation->id, $trigger, $userId, $snapshot,
                $translation->title, // title_snapshot — đóng băng cùng lúc, tránh đọc lại translation trong job
                $restoredFromVersionId,
            )
        );
    }

    private function buildSnapshot(PostArticleTranslation $translation): array
    {
        // Nested qua `contentBlocks.productBlock`/`contentBlocks.faqBlock` (KHÔNG phải quan hệ rời
        // `productBlocks`/`faqBlocks` trên translation) — map() bên dưới truy cập $block->productBlock/
        // $block->faqBlock trên TỪNG dòng contentBlocks, 2 đường quan hệ khác nhau không tự chia sẻ
        // cache cho nhau dù cùng trỏ 1 bảng, thiếu nested sẽ ném LazyLoadingViolationException thật
        // khi Model::shouldBeStrict() bật (đã tái hiện lỗi thật, không phải suy đoán).
        $translation->loadMissing([
            'contentBlocks.productBlock.items.buttons', 'contentBlocks.productBlock.buttons',
            'contentBlocks.faqBlock.items', 'contentBlocks.howtoBlock.steps',
        ]);

        return [
            'translation' => $translation->only([
                'title', 'slug', 'excerpt', 'seo_title', 'seo_description',
                'disclosure_text', 'cta_text', 'cta_url', 'direct_answer',
            ]),
            'blocks' => $translation->contentBlocks->map(function ($block) {
                if ($block->type === ContentBlockType::Text) {
                    return ['type' => 'text', 'text_html' => $block->text_html];
                }

                if ($block->type === ContentBlockType::Faq) {
                    $fb = $block->faqBlock;

                    return [
                        'type'       => 'faq',
                        'block_uuid' => $fb->uuid,
                        'heading'    => $fb->heading,
                        'items'      => $fb->items->map(fn ($item) => [
                            'question' => $item->question,
                            'answer'   => $item->answer,
                        ])->all(),
                    ];
                }

                if ($block->type === ContentBlockType::Citation) {
                    return [
                        'type'                 => 'citation',
                        'citation_text'        => $block->citation_text,
                        'citation_source_name' => $block->citation_source_name,
                        'citation_source_url'  => $block->citation_source_url,
                    ];
                }

                if ($block->type === ContentBlockType::Howto) {
                    $hb = $block->howtoBlock;

                    return [
                        'type'        => 'howto',
                        'block_uuid'  => $hb->uuid,
                        'name'        => $hb->name,
                        'description' => $hb->description,
                        'steps'       => $hb->steps->map(fn ($step) => [
                            'name' => $step->name,
                            'text' => $step->text,
                        ])->all(),
                    ];
                }

                $pb = $block->productBlock;

                return [
                    'type'          => 'product',
                    'block_uuid'    => $pb->uuid,
                    'template'      => $pb->template->value,
                    'heading'       => $pb->heading,
                    'items'         => $pb->items->map(fn ($item) => [
                        'item_key'              => $item->item_key,
                        'product_id'            => $item->product_id,
                        'title_override'        => $item->title_override,
                        'price_label_override'  => $item->price_label_override,
                        'description_override'  => $item->description_override,
                        'image_url_override'    => $item->image_url_override,
                        'buttons'               => $item->buttons->map(fn ($b) => $this->buttonSnapshot($b))->all(),
                    ])->all(),
                    'block_buttons' => $pb->buttons->whereNull('block_item_id')
                        ->map(fn ($b) => $this->buttonSnapshot($b))->values()->all(),
                ];
            })->all(),
        ];
    }

    private function buttonSnapshot($button): array
    {
        return [
            'button_key'        => $button->button_key,
            'label'             => $button->label,
            'url_type'          => $button->url_type->value,
            'url'               => $button->url,
            'product_link_type' => $button->product_link_type,
            'target'            => $button->target->value,
            'style'             => $button->style->value,
        ];
    }
}
