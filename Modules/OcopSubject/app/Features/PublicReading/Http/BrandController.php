<?php

namespace Modules\OcopSubject\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use App\Services\Media\MediaUrlService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\OcopSubject\Features\PublicReading\Queries\ListBrandProductsHandler;
use Modules\OcopSubject\Features\PublicReading\Queries\ListBrandProductsQuery;
use Modules\OcopSubject\Models\OcopSubject;

class BrandController extends Controller
{
    private const STAR_LEVELS = [5, 4, 3];

    public function show(Request $request, string $slug, ListBrandProductsHandler $handler, MediaUrlService $mediaUrl): View
    {
        $subject = OcopSubject::active()->where('slug', $slug)->with('media')->first();

        abort_unless($subject, 404);

        $filters = [
            'q' => $request->string('q')->trim()->value(),
            'sort' => array_key_exists((string) $request->query('sort'), ListBrandProductsQuery::SORTS) ? (string) $request->query('sort') : 'newest',
            'stars' => collect((array) $request->query('stars', []))
                ->map(fn ($star) => (int) $star)
                ->intersect(self::STAR_LEVELS)
                ->unique()
                ->values()
                ->all(),
        ];

        $products = $handler->handle(new ListBrandProductsQuery(
            ocopSubjectId: $subject->id,
            search: $filters['q'] ?: null,
            stars: $filters['stars'],
            sort: $filters['sort'],
            page: max(1, $request->integer('page', 1)),
        ));

        $starCounts = $subject->products()->published()
            ->selectRaw('star_rating, count(*) as total')
            ->groupBy('star_rating')
            ->pluck('total', 'star_rating');

        $gallery = $subject->getMedia(OcopSubject::IMAGE_COLLECTION)
            ->map(fn ($media) => [
                'full' => $mediaUrl->url($media, 'medium'),
                'thumb' => $mediaUrl->url($media, 'thumb'),
            ])
            ->values();

        $certificates = collect(OcopSubject::DOCUMENT_LABELS)
            ->map(fn (string $label, string $collection) => [
                'label' => $label,
                'files' => $subject->getMedia($collection)->map(fn ($media) => [
                    'url' => $mediaUrl->url($media),
                    'name' => $media->name ?: $media->file_name,
                    'is_image' => str_starts_with((string) $media->mime_type, 'image/'),
                    'size' => $media->human_readable_size,
                ])->values(),
            ])
            ->filter(fn (array $group) => $group['files']->isNotEmpty())
            ->values();

        return view('ocopsubject::public.show', [
            'subject' => $subject,
            'products' => $products,
            'filters' => $filters,
            'sorts' => ListBrandProductsQuery::SORTS,
            'starLevels' => self::STAR_LEVELS,
            'starCounts' => $starCounts,
            'gallery' => $gallery,
            'certificates' => $certificates,
        ]);
    }
}
