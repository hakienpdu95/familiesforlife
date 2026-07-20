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
        $translation->loadMissing(['contentBlocks', 'productBlocks.items.buttons', 'productBlocks.buttons']);

        return [
            'translation' => $translation->only([
                'title', 'slug', 'excerpt', 'seo_title', 'seo_description',
                'disclosure_text', 'cta_text', 'cta_url',
            ]),
            'blocks' => $translation->contentBlocks->map(function ($block) {
                if ($block->type === ContentBlockType::Text) {
                    return ['type' => 'text', 'text_html' => $block->text_html];
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
