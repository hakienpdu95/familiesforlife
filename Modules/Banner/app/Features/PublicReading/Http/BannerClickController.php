<?php

namespace Modules\Banner\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Banner\Features\PublicReading\Actions\RecordBannerClickAction;
use Modules\Banner\Models\Banner;

class BannerClickController extends Controller
{
    public function redirect(Banner $banner, RecordBannerClickAction $action): RedirectResponse
    {
        $targetUrl = $action->handle($banner);

        if (! $targetUrl) {
            abort(404);
        }

        return redirect()->away($targetUrl);
    }
}
