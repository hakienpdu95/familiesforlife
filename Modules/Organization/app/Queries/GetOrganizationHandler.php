<?php

namespace Modules\Organization\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Organization\Models\Organization;

class GetOrganizationHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Organization
    {
        /** @var GetOrganizationQuery $query */
        $organization = $query->organization;

        $organization->loadCount('members')->load(['province', 'ward']);

        $organization->setRelation(
            'latestMembers',
            $organization->members()->with('user')->latest()->limit($query->membersLimit)->get()
        );

        return $organization;
    }
}
