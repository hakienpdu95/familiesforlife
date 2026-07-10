<?php

namespace Modules\Aicem\Features\KnowledgeBase\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Models\AicemKnowledgeDocument;

class DeleteKnowledgeDocumentAction
{
    use AsAction;

    public function handle(AicemKnowledgeDocument $document): void
    {
        $document->delete();
    }
}
