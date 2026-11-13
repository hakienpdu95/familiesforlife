<?php

namespace App\Http\Controllers\Backend;

use App\Actions\BuildTimezoneOptionsAction;
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
            'timezoneGroups' => BuildTimezoneOptionsAction::run(),
        ]);
    }

    public function update(Request $request, UpdateSystemSettingsAction $action): RedirectResponse
    {
        Gate::authorize('manageSystemSettings');

        if ($request->filled('ga_measurement_id')) {
            $request->merge(['ga_measurement_id' => strtoupper(trim((string) $request->input('ga_measurement_id')))]);
        }

        $validated = $request->validate([
            'locale' => ['required', Rule::in(array_keys(SystemSetting::LOCALES))],
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
            'date_format' => ['required', Rule::in(SystemSetting::DATE_FORMATS)],
            'time_format' => ['required', Rule::in(SystemSetting::TIME_FORMATS)],
            'show_portal_locale_switcher' => ['boolean'],
            'ga_measurement_id' => ['nullable', 'string', 'max:32', 'regex:'.SystemSetting::GA_MEASUREMENT_ID_PATTERN],
        ], [
            'ga_measurement_id.regex' => __('Measurement ID phải có dạng G-XXXXXXX.'),
        ]);

        $action->handle($validated);

        app()->setLocale($validated['locale']);

        return redirect()->route('backend.settings.index')->with('success', __('Đã lưu cài đặt hệ thống.'));
    }
}
