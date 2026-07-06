<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $service,
    ) {}

    public function index()
    {
        $user = auth()->user();
        $data = $this->service->getData($user);

        return view('backend.dashboard.index', array_merge($data, [
            'greeting'   => $this->greeting(),
            'today_str'  => Carbon::now()->isoFormat('dddd, D MMMM YYYY'),
        ]));
    }

    private function greeting(): string
    {
        $hour = (int) now()->format('H');
        return match(true) {
            $hour < 12 => 'Chào buổi sáng',
            $hour < 18 => 'Chào buổi chiều',
            default    => 'Chào buổi tối',
        };
    }
}
