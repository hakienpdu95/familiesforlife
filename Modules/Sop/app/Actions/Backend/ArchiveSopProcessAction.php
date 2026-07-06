<?php

namespace Modules\Sop\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Sop\Models\SopProcess;

class ArchiveSopProcessAction
{
    use AsAction;

    public function handle(SopProcess $sop): string
    {
        $code = $sop->code;

        $sop->update([
            'status'     => 'archived',
            'updated_by' => auth()->id(),
        ]);

        $sop->delete();

        return $code;
    }
}
