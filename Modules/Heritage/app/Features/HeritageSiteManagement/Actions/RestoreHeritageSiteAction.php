<?php

namespace Modules\Heritage\Features\HeritageSiteManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Heritage\Models\HeritageSite;

/** restore() gọi save() nên HasQrIdentity tự làm mới snapshot — mã QR đã in quay về trang thật. */
class RestoreHeritageSiteAction
{
    use AsAction;

    public function handle(HeritageSite $site): HeritageSite
    {
        $site->restore();

        return $site;
    }
}
