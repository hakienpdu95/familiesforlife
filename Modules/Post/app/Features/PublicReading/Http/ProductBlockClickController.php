<?php

namespace Modules\Post\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Post\Features\PublicReading\Actions\RecordProductBlockClickAction;
use Modules\Post\Models\PostProductBlockButton;

class ProductBlockClickController extends Controller
{
    public function redirect(PostProductBlockButton $button, RecordProductBlockClickAction $action): RedirectResponse
    {
        $targetUrl = $action->handle($button);

        if (! $targetUrl) {
            abort(404);
        }

        return redirect()->away($targetUrl);
    }
}
