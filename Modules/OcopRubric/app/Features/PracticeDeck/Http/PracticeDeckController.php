<?php

namespace Modules\OcopRubric\Features\PracticeDeck\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\OcopRubric\Features\PracticeDeck\Queries\GetPracticeHistoryHandler;
use Modules\OcopRubric\Features\PracticeDeck\Queries\GetPracticeHistoryQuery;
use Modules\OcopRubric\Models\OcopProduct;

class PracticeDeckController extends Controller
{
    /** Bước 1 — chọn sản phẩm để luyện tập. */
    public function start(): View
    {
        $products = OcopProduct::with('productGroup')
            ->where('status', '!=', 'archived')
            ->orderBy('name')
            ->get();

        return view('ocoprubric::practice.start', compact('products'));
    }

    public function history(Request $request, GetPracticeHistoryHandler $handler): View
    {
        $history = $handler->handle(new GetPracticeHistoryQuery(
            productId: $request->integer('product_id') ?: null,
            mode: $request->string('mode')->value() ?: null,
            page: max(1, $request->integer('page', 1)),
        ));

        $products = OcopProduct::orderBy('name')->get();

        return view('ocoprubric::practice.history', compact('history', 'products'));
    }
}
