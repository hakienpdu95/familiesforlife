@extends('layouts.backend')

@section('title', __('Cài đặt hệ thống'))

@section('content')

@php
    $sample = now();
    $currentLocale = old('locale', $settings->locale);
    $currentTimezone = old('timezone', $settings->timezone);
    $currentDateFormat = old('date_format', $settings->date_format);
    $currentTimeFormat = old('time_format', $settings->time_format);
@endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-base-content">{{ __('Cài đặt hệ thống') }}</h1>
    <p class="text-sm text-base-content/50 mt-0.5">{{ __('Ngôn ngữ giao diện, múi giờ và định dạng ngày giờ dùng chung cho trang quản trị và cổng thông tin.') }}</p>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 text-sm">
    <ul class="list-disc list-inside space-y-0.5 text-xs">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('backend.settings.update') }}" class="max-w-2xl">
    @csrf
    @method('PUT')

    <div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
        <div class="card-body">
            <h2 class="card-title text-base mb-1">{{ __('Ngôn ngữ') }}</h2>
            <p class="text-xs text-base-content/40 mb-4">{{ __('Áp dụng cho toàn bộ giao diện quản trị, cổng thông tin, email và thông báo. Không thay đổi ngôn ngữ của nội dung bài viết.') }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach(\App\Models\SystemSetting::LOCALES as $code => $label)
                <label class="flex items-center gap-3 rounded-box border border-base-300 px-4 py-3 cursor-pointer has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                    <input type="radio" name="locale" value="{{ $code }}" class="radio radio-primary radio-sm" {{ $currentLocale === $code ? 'checked' : '' }}>
                    <span class="font-medium text-sm">{{ $label }}</span>
                    <span class="ml-auto text-xs font-mono text-base-content/40">{{ $code }}</span>
                </label>
                @endforeach
            </div>
            @error('locale')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
        <div class="card-body">
            <h2 class="card-title text-base mb-4">{{ __('Múi giờ và định dạng') }}</h2>

            <div class="form-control mb-4">
                <label class="label py-0 pb-1.5" for="timezone"><span class="label-text font-medium">{{ __('Múi giờ hiển thị') }}</span></label>
                <select id="timezone" name="timezone" class="select select-bordered select-sm w-full max-w-sm @error('timezone') select-error @enderror">
                    @foreach($timezones as $tz)
                    <option value="{{ $tz }}" {{ $currentTimezone === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                    @endforeach
                </select>
                @error('timezone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label py-0 pb-1.5" for="date_format"><span class="label-text font-medium">{{ __('Định dạng ngày') }}</span></label>
                    <select id="date_format" name="date_format" class="select select-bordered select-sm w-full @error('date_format') select-error @enderror">
                        @foreach(\App\Models\SystemSetting::DATE_FORMATS as $format)
                        <option value="{{ $format }}" {{ $currentDateFormat === $format ? 'selected' : '' }}>{{ $sample->format($format) }} ({{ $format }})</option>
                        @endforeach
                    </select>
                    @error('date_format')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1.5" for="time_format"><span class="label-text font-medium">{{ __('Định dạng giờ') }}</span></label>
                    <select id="time_format" name="time_format" class="select select-bordered select-sm w-full @error('time_format') select-error @enderror">
                        @foreach(\App\Models\SystemSetting::TIME_FORMATS as $format)
                        <option value="{{ $format }}" {{ $currentTimeFormat === $format ? 'selected' : '' }}>{{ $sample->format($format) }} ({{ $format }})</option>
                        @endforeach
                    </select>
                    @error('time_format')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <p class="mt-4 text-xs text-base-content/50">{{ __('Hiện tại') }}: <span class="font-medium text-base-content/80">{{ format_datetime($sample) }}</span></p>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary btn-sm">{{ __('Lưu cài đặt') }}</button>
    </div>
</form>

@endsection
