<?php

namespace Modules\Marketplace\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Marketplace\Http\Resources\MktApplicantResource;
use Modules\Marketplace\Http\Resources\MktListingListResource;
use Modules\Marketplace\Models\MktApplicant;
use Modules\Marketplace\Models\MktListing;
use Modules\Marketplace\Models\MktTag;

class PublicListingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'work_type', 'experience_level', 'search', 'tag', 'page']);
        $cacheKey = 'mkt:listings:browse:' . md5(serialize($filters));

        $result = Cache::remember($cacheKey, 120, function () use ($request) {
            $query = MktListing::withoutGlobalScope('tenant')
                ->with('organization:id,name,logo_path')
                ->active()
                ->public();

            if ($type = $request->input('type')) {
                $query->where('listing_type', $type);
            }
            if ($workType = $request->input('work_type')) {
                $query->where('work_type', $workType);
            }
            if ($level = $request->input('experience_level')) {
                $query->where('experience_level', $level);
            }
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%");
                });
            }
            if ($tag = $request->input('tag')) {
                $query->whereHas('tags', fn($q) => $q->where('slug', $tag));
            }

            $listings = $query->orderByDesc('created_at')->paginate(20);

            return [
                'data'      => MktListingListResource::collection($listings->items())->toArray(request()),
                'last_page' => $listings->lastPage(),
                'total'     => $listings->total(),
            ];
        });

        return response()->json($result);
    }

    public function show(string $slug): JsonResponse
    {
        $listing = MktListing::withoutGlobalScope('tenant')
            ->with(['organization:id,name,logo_path,website', 'tags'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Increment view_count outside cache
        $listing->increment('view_count');
        Cache::forget('mkt:listing:' . $slug);

        $data = Cache::remember('mkt:listing:' . $slug, 300, function () use ($listing) {
            return (new MktListingListResource($listing->fresh(['organization', 'tags'])))->toArray(request());
        });

        return response()->json($data);
    }

    public function similar(string $slug): JsonResponse
    {
        $listing = MktListing::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->firstOrFail();

        $similar = MktListing::withoutGlobalScope('tenant')
            ->with('organization:id,name,logo_path')
            ->active()
            ->public()
            ->where('listing_type', $listing->listing_type)
            ->where('id', '!=', $listing->id)
            ->limit(6)
            ->get();

        return response()->json(MktListingListResource::collection($similar));
    }

    public function trending(): JsonResponse
    {
        $data = Cache::remember('mkt:listings:trending', 300, function () {
            $listings = MktListing::withoutGlobalScope('tenant')
                ->with('organization:id,name,logo_path')
                ->active()
                ->public()
                ->orderByRaw('(view_count + application_count * 3) DESC')
                ->limit(10)
                ->get();

            return MktListingListResource::collection($listings)->toArray(request());
        });

        return response()->json($data);
    }

    public function profile(string $slug): JsonResponse
    {
        $data = Cache::remember('mkt:profile:' . $slug, 600, function () use ($slug) {
            $applicant = MktApplicant::where('slug', $slug)
                ->where('is_profile_public', true)
                ->firstOrFail();

            $applicant->load(['skills', 'experiences', 'portfolios']);

            return (new MktApplicantResource($applicant))->toArray(request());
        });

        return response()->json($data);
    }

    public function tags(): JsonResponse
    {
        $data = Cache::remember('mkt:tags:popular', 1800, function () {
            return MktTag::orderByDesc('use_count')
                ->limit(50)
                ->get(['id', 'uuid', 'name', 'slug', 'use_count'])
                ->toArray();
        });

        return response()->json($data);
    }
}
