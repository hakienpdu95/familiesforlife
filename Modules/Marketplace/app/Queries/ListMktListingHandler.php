<?php

namespace Modules\Marketplace\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Marketplace\Models\MktListing;

class ListMktListingHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'title', 'status', 'listing_type', 'poster_type', 'work_type',
        'application_count', 'view_count', 'created_at', 'expire_at',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListMktListingQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'created_at';

        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = $query->adminScope
            ? MktListing::withoutTenant()
            : MktListing::query();

        $q->select('mkt_listings.*')
          ->with(['organization:id,name,logo_path', 'postedBy:id,name']);

        // ── Text search ─────────────────────────────────────────────
        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('mkt_listings.title',    'like', $term)
                    ->orWhere('mkt_listings.location', 'like', $term);
            });
        }

        // ── Exact filters ────────────────────────────────────────────
        if ($query->status !== null && $query->status !== '') {
            $q->where('mkt_listings.status', $query->status);
        }

        if ($query->listingType !== null && $query->listingType !== '') {
            $q->where('mkt_listings.listing_type', $query->listingType);
        }

        if ($query->posterType !== null && $query->posterType !== '') {
            $q->where('mkt_listings.poster_type', $query->posterType);
        }

        if ($query->workType !== null && $query->workType !== '') {
            $q->where('mkt_listings.work_type', $query->workType);
        }

        if ($query->experienceLevel !== null && $query->experienceLevel !== '') {
            $q->where('mkt_listings.experience_level', $query->experienceLevel);
        }

        // ── Date range ───────────────────────────────────────────────
        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->whereDate('mkt_listings.created_at', '>=', $query->dateFrom);
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->whereDate('mkt_listings.created_at', '<=', $query->dateTo);
        }

        // ── Sort ─────────────────────────────────────────────────────
        $q->orderBy('mkt_listings.' . $sortField, $sortDir);

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
