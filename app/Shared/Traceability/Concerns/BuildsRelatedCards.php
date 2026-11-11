<?php

namespace App\Shared\Traceability\Concerns;

use App\Services\Media\MediaUrlService;
use App\Shared\Traceability\TraceabilityService as T;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Heritage\Enums\HeritageSiteStatus;
use Modules\Heritage\Models\HeritageSite;
use Modules\Ocop\Enums\OcopProductStatus;
use Modules\Ocop\Models\OcopProduct;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Models\PostArticle;

trait BuildsRelatedCards
{
    protected function mediaUrls(Model $model, string $collection, string $conversion = 'medium'): array
    {
        if (! method_exists($model, 'getMedia')) {
            return [];
        }

        $urls = app(MediaUrlService::class);

        return $model->getMedia($collection)->map(fn ($media) => $urls->url($media, $conversion))->filter()->values()->all();
    }

    protected function mediaSrcsets(Model $model, string $collection, string $conversion = 'medium'): array
    {
        if (! method_exists($model, 'getMedia')) {
            return [];
        }

        $urls = app(MediaUrlService::class);

        return $model->getMedia($collection)
            ->filter(fn ($media) => filled($urls->url($media, $conversion)))
            ->map(fn ($media) => T::srcset($media))
            ->values()->all();
    }

    /** @param  Collection<int, OcopProduct>  $products */
    protected function ocopCards(Collection $products, bool $linkToQr = false): array
    {
        return $products
            ->filter(fn (OcopProduct $p) => $p->status === OcopProductStatus::Published && ! $p->trashed())
            ->map(fn (OcopProduct $p) => [
                'url' => ($linkToQr ? $p->qrIdentity?->publicUrl() : null) ?? route('ocop.public.show', ['slug' => $p->slug, 'id' => $p->id]),
                'title' => $p->name,
                'subtitle' => $p->star_rating ? str_repeat('★', $p->star_rating).' OCOP '.$p->star_rating.' sao' : 'Sản phẩm OCOP',
                'image' => $p->imageUrl('thumb') ?: null,
            ])->values()->all();
    }

    /** @param  Collection<int, PostArticle>  $articles */
    protected function articleCards(Collection $articles): array
    {
        return $articles
            ->map(function (PostArticle $a) {
                $t = $a->translations->where('locale', config('post.default_locale'))->firstWhere('status', TranslationStatus::Published);

                return $t ? [
                    'url' => route('post.public.article', ['slug' => $t->slug, 'id' => $t->id]),
                    'title' => $t->title,
                    'subtitle' => format_date($t->published_at),
                    'image' => $a->getFirstMediaUrl('cover', 'thumb') ?: null,
                ] : null;
            })->filter()->values()->all();
    }

    /** @param  Collection<int, HeritageSite>  $sites */
    protected function heritageCards(Collection $sites): array
    {
        return $sites
            ->filter(fn (HeritageSite $s) => $s->status === HeritageSiteStatus::Published && ! $s->trashed())
            ->map(fn (HeritageSite $s) => [
                'url' => route('heritage.public.show', ['slug' => $s->slug, 'id' => $s->id]),
                'title' => $s->name,
                'subtitle' => $s->rank?->label(),
                'image' => $s->getFirstMediaUrl('cover', 'thumb') ?: null,
            ])->values()->all();
    }

    protected function docStatus(Model $model, array $labels): array
    {
        return collect($labels)->map(function (string $label, string $collection) use ($model) {
            $media = $model->getMedia($collection);

            return [
                'label' => __($label),
                'count' => $media->count(),
                'updated_at' => format_date($media->max('updated_at')),
            ];
        })->values()->all();
    }
}
