<?php

namespace Modules\Page\Features\PageManagement\Actions;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Features\PageManagement\Data\PageData;
use Modules\Page\Models\Page;

class CreatePageAction
{
    use AsAction;

    /**
     * spec/Page_Static_Pages_Technical_Specification.md §3.3 — slug tự sinh từ title khi để
     * trống, luôn tạo ở trạng thái draft (xuất bản là 1 hành động riêng, xem PublishPageAction).
     */
    public function handle(PageData $data): Page
    {
        $slug = $data->slug !== null && $data->slug !== ''
            ? $data->slug
            : self::uniqueSlug(Str::slug($data->title) ?: Str::random(8));

        return Page::create([
            'slug'             => $slug,
            'title'            => $data->title,
            'template'         => $data->template,
            'content'          => $data->content,
            'excerpt'          => $data->excerpt,
            'status'           => PageStatus::Draft,
            'seo_title'        => $data->seo_title,
            'seo_description'  => $data->seo_description,
            'seo_noindex'      => $data->seo_noindex,
            'sort_order'       => $data->sort_order,
            'created_by'       => auth()->id(),
        ]);
    }

    /**
     * Đảm bảo unique thật (kể cả với bản ghi đã soft-delete — unique constraint DB không loại
     * trừ deleted_at) và không trùng reserved_slugs (§4.1) khi slug được tự sinh từ title.
     */
    public static function uniqueSlug(string $base): string
    {
        $reserved = config('page.reserved_slugs', []);
        $slug     = $base;
        $i        = 2;

        while (in_array($slug, $reserved, true) || Page::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
