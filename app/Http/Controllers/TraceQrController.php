<?php

namespace App\Http\Controllers;

use App\Shared\Traceability\TraceabilityService;
use Illuminate\View\View;

class TraceQrController extends Controller
{
    public function __invoke(TraceabilityService $traceability, string $qrCode): View
    {
        $identity = $traceability->resolveQrCode($qrCode);

        $page = $traceability->present($identity);

        return view($page['view'], [
            'brand' => config('app.site_name'),
            ...$page,
        ]);
    }
}
