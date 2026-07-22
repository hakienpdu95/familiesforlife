<?php

namespace Modules\Event\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\Event\Enums\EventStatus;
use Modules\Event\Models\Event;

class EventSitemapController extends Controller
{
    public function index(): Response
    {
        $events = Event::where('status', EventStatus::Published)
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'slug', 'updated_at']);

        return response()
            ->view('event::public.sitemap', compact('events'))
            ->header('Content-Type', 'text/xml');
    }
}
