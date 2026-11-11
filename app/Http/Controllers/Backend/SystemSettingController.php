<?php

namespace App\Http\Controllers\Backend;

use App\Actions\UpdateSystemSettingsAction;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manageSystemSettings');

        return view('backend.settings.index', [
            'settings' => SystemSetting::current(),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function update(Request $request, UpdateSystemSettingsAction $action): RedirectResponse
    {
        Gate::authorize('manageSystemSettings');

        $validated = $request->validate([
            'locale' => ['required', Rule::in(array_keys(SystemSetting::LOCALES))],
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
            'date_format' => ['required', Rule::in(SystemSetting::DATE_FORMATS)],
            'time_format' => ['required', Rule::in(SystemSetting::TIME_FORMATS)],
        ]);

        $action->handle($validated);

        app()->setLocale($validated['locale']);

        return redirect()->route('backend.settings.index')->with('success', __('Đã lưu cài đặt hệ thống.'));
    }
}
