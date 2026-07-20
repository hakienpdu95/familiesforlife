<?php

namespace Modules\Newsletter\Features\SubscriberManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Newsletter\Models\NewsletterSubscriber;

class ListSubscribersForAdminHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListSubscribersForAdminQuery $query */
        return NewsletterSubscriber::query()
            ->orderByDesc('subscribed_at')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
