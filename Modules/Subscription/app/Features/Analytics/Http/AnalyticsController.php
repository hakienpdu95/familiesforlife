<?php

namespace Modules\Subscription\Features\Analytics\Http;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Subscription\Features\Analytics\Queries\GetSubscriptionAnalyticsHandler;
use Modules\Subscription\Features\Analytics\Queries\GetSubscriptionAnalyticsQuery;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly GetSubscriptionAnalyticsHandler $handler,
    ) {}

    public function index(Request $request)
    {
        $year  = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $data = $this->handler->handle(new GetSubscriptionAnalyticsQuery($year, $month));

        return view('subscription::admin.analytics.index', array_merge($data, [
            'year'  => $year,
            'month' => $month,
        ]));
    }
}
