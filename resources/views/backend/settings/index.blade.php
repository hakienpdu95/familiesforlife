@extends('layouts.backend')

@section('title', __('Cài đặt hệ thống'))

@section('content')

@php
    $sample = now();
    $currentLocale = old('locale', $settings->locale);
    $currentTimezone = old('timezone', $settings->timezone);
    $currentDateFormat = old('date_format', $settings->date_format);
    $currentTimeFormat = old('time_format', $settings->time_format);
    $localeHints = [
        'vi' => __('Mặc định cho người dùng trong nước'),
        'en' => __('Dành cho đối tác và người dùng quốc tế'),
    ];
@endphp

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ __('Cài đặt hệ thống') }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ __('Ngôn ngữ giao diện, múi giờ và định dạng ngày giờ dùng chung cho trang quản trị và cổng thông tin.') }}</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="badge badge-warning badge-sm gap-1 hidden" data-settings-dirty>
            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>{{ __('Chưa lưu') }}
        </span>
        <button type="reset" form="system-settings-form" class="btn btn-ghost btn-sm" data-settings-reset disabled>{{ __('Hoàn tác') }}</button>
        <button type="submit" form="system-settings-form" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ __('Lưu cài đặt') }}
        </button>
    </div>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <div>
        <p class="font-semibold">{{ __('Có :count lỗi cần kiểm tra:', ['count' => $errors->count()]) }}</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form id="system-settings-form" method="POST" action="{{ route('backend.settings.update') }}" novalidate data-settings-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px] gap-6 items-start">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-0 divide-y divide-base-200">

                <section class="grid grid-cols-1 lg:grid-cols-[240px_minmax(0,1fr)] gap-x-8 gap-y-3 p-5 lg:p-6">
                    <div>
                        <h2 class="text-sm font-semibold text-base-content flex items-center gap-2">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                            {{ __('Ngôn ngữ') }}
                        </h2>
                        <p class="text-xs text-base-content/50 mt-1 leading-relaxed">{{ __('Áp dụng cho toàn bộ giao diện quản trị, cổng thông tin, email và thông báo. Không thay đổi ngôn ngữ của nội dung bài viết.') }}</p>
                    </div>
                    <div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach(\App\Models\SystemSetting::LOCALES as $code => $label)
                            <label class="flex items-center gap-3 rounded-box border border-base-300 px-4 py-3 cursor-pointer transition-colors hover:border-primary/50 has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:ring-1 has-[:checked]:ring-primary/30">
                                <input type="radio" name="locale" value="{{ $code }}" class="radio radio-primary radio-sm" data-settings-field {{ $currentLocale === $code ? 'checked' : '' }}>
                                <span class="min-w-0">
                                    <span class="block font-medium text-sm">{{ $label }}</span>
                                    <span class="block text-xs text-base-content/45 leading-snug">{{ $localeHints[$code] ?? '' }}</span>
                                </span>
                                <span class="ml-auto badge badge-ghost badge-sm font-mono uppercase">{{ $code }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('locale')<p class="mt-1.5 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section class="grid grid-cols-1 lg:grid-cols-[240px_minmax(0,1fr)] gap-x-8 gap-y-3 p-5 lg:p-6">
                    <div>
                        <h2 class="text-sm font-semibold text-base-content flex items-center gap-2">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                            {{ __('Cổng thông tin') }}
                        </h2>
                        <p class="text-xs text-base-content/50 mt-1 leading-relaxed">{{ __('Tuỳ chọn hiển thị trên header trang công khai.') }}</p>
                    </div>
                    <div>
                        <label class="flex items-center justify-between gap-4 rounded-box border border-base-300 px-4 py-3 cursor-pointer select-none">
                            <span class="min-w-0">
                                <span class="block font-medium text-sm">{{ __('Hiện nút đổi ngôn ngữ') }}</span>
                                <span class="block text-xs text-base-content/45 leading-snug">{{ __('Tắt để ẩn nút VI | EN trên header. Các URL /en/... vẫn hoạt động bình thường.') }}</span>
                            </span>
                            <input type="hidden" name="show_portal_locale_switcher" value="0" data-settings-field>
                            <input type="checkbox" name="show_portal_locale_switcher" value="1" class="toggle toggle-primary toggle-sm shrink-0" data-settings-field
                                   @checked(old('show_portal_locale_switcher', $settings->show_portal_locale_switcher ?? true))>
                        </label>
                        @error('show_portal_locale_switcher')<p class="mt-1.5 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section class="grid grid-cols-1 lg:grid-cols-[240px_minmax(0,1fr)] gap-x-8 gap-y-3 p-5 lg:p-6">
                    <div>
                        <h2 class="text-sm font-semibold text-base-content flex items-center gap-2">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            {{ __('Google Analytics') }}
                        </h2>
                        <p class="text-xs text-base-content/50 mt-1 leading-relaxed">{{ __('Gắn mã theo dõi GA4 lên cổng thông tin và trang truy xuất QR. Không gắn trên trang quản trị.') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5" for="ga_measurement_id">{{ __('Measurement ID') }}</label>
                        <input type="text" id="ga_measurement_id" name="ga_measurement_id" maxlength="32" autocomplete="off" spellcheck="false" placeholder="G-XXXXXXXXXX" data-settings-field
                               value="{{ old('ga_measurement_id', $settings->ga_measurement_id) }}"
                               class="input input-bordered input-sm w-full sm:max-w-xs font-mono uppercase @error('ga_measurement_id') input-error @enderror">
                        @error('ga_measurement_id')<p class="mt-1.5 text-xs text-error">{{ $message }}</p>@enderror
                        <p class="mt-1.5 text-xs text-base-content/45 leading-snug">{{ __('Lấy tại Google Analytics → Admin → Data streams → chọn luồng Web. Để trống để tắt theo dõi.') }} {{ __('Không ghi nhận lượt xem của tài khoản quản trị đang đăng nhập.') }}</p>
                        @if(\App\Models\SystemSetting::gaMeasurementId() && ! \App\Support\Analytics\GoogleAnalyticsTag::enabledForEnvironment())
                        <span class="badge badge-warning badge-soft badge-sm mt-2">{{ __('Đã lưu — đang tắt ở môi trường :env', ['env' => app()->environment()]) }}</span>
                        @elseif(\App\Models\SystemSetting::gaMeasurementId())
                        <span class="badge badge-success badge-soft badge-sm mt-2">{{ __('Đang theo dõi') }}</span>
                        @else
                        <span class="badge badge-ghost badge-sm mt-2">{{ __('Chưa bật') }}</span>
                        @endif
                    </div>
                </section>

                <section class="grid grid-cols-1 lg:grid-cols-[240px_minmax(0,1fr)] gap-x-8 gap-y-3 p-5 lg:p-6">
                    <div>
                        <h2 class="text-sm font-semibold text-base-content flex items-center gap-2">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ __('Múi giờ') }}
                        </h2>
                        <p class="text-xs text-base-content/50 mt-1 leading-relaxed">{{ __('Mọi thời điểm lưu trong hệ thống được quy đổi sang múi giờ này khi hiển thị.') }}</p>
                    </div>
                    <div>
                        <label class="sr-only" for="timezone">{{ __('Múi giờ hiển thị') }}</label>
                        <select id="timezone" name="timezone" data-settings-field data-settings-timezone
                                data-ts-placeholder="{{ __('Tìm theo thành phố hoặc UTC...') }}"
                                class="select select-bordered select-sm w-full @error('timezone') select-error @enderror">
                            @foreach($timezoneGroups as $region => $zones)
                            <optgroup label="{{ $region }}">
                                @foreach($zones as $zone)
                                <option value="{{ $zone['value'] }}" data-utc="{{ $zone['utc'] }}" data-city="{{ $zone['city'] }}" {{ $currentTimezone === $zone['value'] ? 'selected' : '' }}>{{ $zone['value'] }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        @error('timezone')<p class="mt-1.5 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section class="grid grid-cols-1 lg:grid-cols-[240px_minmax(0,1fr)] gap-x-8 gap-y-3 p-5 lg:p-6">
                    <div>
                        <h2 class="text-sm font-semibold text-base-content flex items-center gap-2">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ __('Định dạng ngày giờ') }}
                        </h2>
                        <p class="text-xs text-base-content/50 mt-1 leading-relaxed">{{ __('Dùng cho bảng dữ liệu, bộ lọc, email và trang công khai.') }}</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-base-content/70 mb-1.5" for="date_format">{{ __('Định dạng ngày') }}</label>
                            <select id="date_format" name="date_format" data-settings-field data-settings-format
                                    class="select select-bordered select-sm w-full @error('date_format') select-error @enderror">
                                @foreach(\App\Models\SystemSetting::DATE_FORMATS as $format)
                                <option value="{{ $format }}" data-pattern="{{ $format }}" {{ $currentDateFormat === $format ? 'selected' : '' }}>{{ $sample->format($format) }}</option>
                                @endforeach
                            </select>
                            @error('date_format')<p class="mt-1.5 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-base-content/70 mb-1.5" for="time_format">{{ __('Định dạng giờ') }}</label>
                            <select id="time_format" name="time_format" data-settings-field data-settings-format
                                    class="select select-bordered select-sm w-full @error('time_format') select-error @enderror">
                                @foreach(\App\Models\SystemSetting::TIME_FORMATS as $format)
                                <option value="{{ $format }}" data-pattern="{{ $format }}" {{ $currentTimeFormat === $format ? 'selected' : '' }}>{{ $sample->format($format) }}</option>
                                @endforeach
                            </select>
                            @error('time_format')<p class="mt-1.5 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

            </div>
        </div>

        <aside class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-5">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">{{ __('Xem trước') }}</p>
                    <div class="mt-3 rounded-box bg-base-200/60 px-4 py-4 text-center">
                        <p class="text-3xl font-semibold tabular-nums text-base-content" data-preview-time>{{ $sample->format($currentTimeFormat) }}</p>
                        <p class="text-sm text-base-content/60 mt-1 tabular-nums" data-preview-date>{{ $sample->format($currentDateFormat) }}</p>
                    </div>
                    <dl class="mt-4 space-y-2 text-xs">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-base-content/50">{{ __('Múi giờ') }}</dt>
                            <dd class="font-medium text-right truncate" data-preview-timezone>{{ $currentTimezone }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-base-content/50">{{ __('Ngày giờ đầy đủ') }}</dt>
                            <dd class="font-mono text-right" data-preview-datetime>{{ format_datetime($sample) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-5">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">{{ __('Phạm vi áp dụng') }}</p>
                    <ul class="space-y-2 text-xs text-base-content/70">
                        <li class="flex gap-2"><span class="text-success">●</span>{{ __('Trang quản trị và cổng thông tin') }}</li>
                        <li class="flex gap-2"><span class="text-success">●</span>{{ __('Email, thông báo và tác vụ hàng đợi') }}</li>
                        <li class="flex gap-2"><span class="text-base-content/30">●</span>{{ __('Không đổi ngôn ngữ nội dung bài viết') }}</li>
                    </ul>
                    @if($settings->updated_at)
                    <p class="mt-4 pt-3 border-t border-base-200 text-xs text-base-content/40">{{ __('Cập nhật lần cuối :time', ['time' => format_datetime($settings->updated_at)]) }}</p>
                    @endif
                </div>
            </div>
        </aside>

    </div>
</form>

@endsection

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'resources/js/pages/system-settings.js',
    ], 'build/backend')
@endpush
