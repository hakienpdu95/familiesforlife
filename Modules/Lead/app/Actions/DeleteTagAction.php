<?php

namespace Modules\Lead\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Lead\Events\TagDeleted;
use Modules\Lead\Models\LeadTagDefinition;

class DeleteTagAction
{
    use AsAction;

    public function handle(LeadTagDefinition $tag): void
    {
        $id    = $tag->id;
        $orgId = $tag->organization_id;

        DB::table('lead_tag_map')->where('tag_id', $id)->delete();
        $tag->delete();

        event(new TagDeleted($id, $orgId));
    }
}
