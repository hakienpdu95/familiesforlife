<?php

namespace Modules\Lead\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Lead\Actions\SyncLeadTagsAction;
use Modules\Lead\Models\Lead;
use Modules\Lead\Models\LeadTagDefinition;
use Modules\Lead\Queries\ListTagsHandler;
use Modules\Lead\Queries\ListTagsQuery;

class LeadTagApiController extends Controller
{
    public function list(ListTagsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', LeadTagDefinition::class);

        $tags = $handler->handle(new ListTagsQuery($this->orgId()));

        return response()->json(
            $tags->map(fn (LeadTagDefinition $t) => [
                'id'    => $t->id,
                'text'  => $t->name,
                'color' => $t->color,
            ])
        );
    }

    public function sync(Request $request, Lead $lead, SyncLeadTagsAction $action): JsonResponse
    {
        $this->authorize('update', $lead);

        $validated = $request->validate([
            'tag_ids'   => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'min:1'],
        ]);

        $action->handle($lead, $validated['tag_ids'] ?? []);

        $lead->load('tags');

        return response()->json([
            'ok'   => true,
            'tags' => $lead->tags->map(fn ($t) => [
                'id'    => $t->id,
                'name'  => $t->name,
                'color' => $t->color,
            ]),
        ]);
    }

    private function orgId(): int
    {
        return TenantContext::getOrganizationId() ?? abort(403, 'No organization context.');
    }
}
