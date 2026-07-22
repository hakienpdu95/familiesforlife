<?php

namespace Modules\Newsletter\Features\SubscriberManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Newsletter\Models\NewsletterSubscriber;

class ListSubscribersForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['full_name', 'email', 'status', 'subscribed_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListSubscribersForAdminQuery $query */
        $q = NewsletterSubscriber::query();

        if ($query->search) {
            $term = '%' . $query->search . '%';
            $q->where(fn ($sub) => $sub->where('full_name', 'like', $term)->orWhere('email', 'like', $term));
        }

        if ($query->status) {
            $q->where('status', $query->status);
        }

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'subscribed_at';
        $sortDir   = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return $q->orderBy($sortField, $sortDir)
            ->orderByDesc('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
