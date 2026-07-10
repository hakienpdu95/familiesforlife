@extends('layouts.backend')
@section('title', 'Cấu hình AICEM')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Cấu hình AICEM</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Provider AI (BYOK), hạn mức chi phí, rate limit cho tổ chức của bạn</p>
    </div>
    <a href="{{ route('backend.aicem.dashboard') }}" class="btn btn-ghost btn-sm">← Tổng quan</a>
</div>

@foreach(['success', 'error'] as $type)
    @if(session($type))
    <div class="alert alert-{{ $type }} mb-4 text-sm">{{ session($type) }}</div>
    @endif
@endforeach

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 text-sm">
    <ul class="list-disc list-inside space-y-0.5 text-xs">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('backend.aicem.settings.update') }}" class="max-w-2xl">
    @csrf
    @method('PUT')

    <div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
        <div class="card-body">
            <h2 class="card-title text-base mb-1">Provider AI (BYOK)</h2>
            <p class="text-xs text-base-content/40 mb-4">
                Để trống toàn bộ để dùng provider mặc định của nền tảng (config('ai.default')).
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Provider</span></label>
                    <select name="ai_provider" class="select select-bordered select-sm w-full @error('ai_provider') select-error @enderror">
                        <option value="">— Dùng mặc định nền tảng —</option>
                        <option value="anthropic" {{ old('ai_provider', $organization->ai_provider_config['provider'] ?? '') === 'anthropic' ? 'selected' : '' }}>Anthropic</option>
                        <option value="openai" {{ old('ai_provider', $organization->ai_provider_config['provider'] ?? '') === 'openai' ? 'selected' : '' }}>OpenAI</option>
                    </select>
                    @error('ai_provider')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Model</span></label>
                    <input type="text" name="ai_model" value="{{ old('ai_model', $organization->ai_provider_config['model'] ?? '') }}"
                           class="input input-bordered input-sm w-full font-mono @error('ai_model') input-error @enderror"
                           placeholder="VD: claude-sonnet-5">
                    @error('ai_model')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">API key</span>
                    <span class="label-text-alt text-xs text-base-content/40">Để trống nếu không đổi key hiện có</span>
                </label>
                <input type="password" name="ai_api_key" value=""
                       class="input input-bordered input-sm w-full font-mono @error('ai_api_key') input-error @enderror"
                       placeholder="{{ ($organization->ai_provider_config['api_key'] ?? null) ? '•••••••••••• (đã lưu)' : 'Chưa cấu hình' }}"
                       autocomplete="new-password">
                @error('ai_api_key')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
        <div class="card-body">
            <h2 class="card-title text-base mb-4">Hạn mức chi phí</h2>
            <div class="form-control">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Hạn mức/tháng (USD)</span>
                    <span class="label-text-alt text-xs text-base-content/40">Để trống = không giới hạn</span>
                </label>
                <input type="number" step="0.01" min="0" name="ai_monthly_budget_usd"
                       value="{{ old('ai_monthly_budget_usd', $organization->ai_monthly_budget_usd) }}"
                       class="input input-bordered input-sm w-full max-w-xs @error('ai_monthly_budget_usd') input-error @enderror"
                       placeholder="VD: 50.00">
                @error('ai_monthly_budget_usd')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
        <div class="card-body">
            <h2 class="card-title text-base mb-1">Rate limit</h2>
            <p class="text-xs text-base-content/40 mb-4">Để trống = dùng mặc định config('aicem.rate_limit') ({{ config('aicem.rate_limit.per_minute') }}/phút, {{ config('aicem.rate_limit.per_day') }}/ngày).</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Lượt/phút mỗi user</span></label>
                    <input type="number" min="1" name="rate_limit_per_minute"
                           value="{{ old('rate_limit_per_minute', $organization->ai_rate_limit_override['per_minute'] ?? '') }}"
                           class="input input-bordered input-sm w-full @error('rate_limit_per_minute') input-error @enderror">
                    @error('rate_limit_per_minute')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Lượt/ngày mỗi user</span></label>
                    <input type="number" min="1" name="rate_limit_per_day"
                           value="{{ old('rate_limit_per_day', $organization->ai_rate_limit_override['per_day'] ?? '') }}"
                           class="input input-bordered input-sm w-full @error('rate_limit_per_day') input-error @enderror">
                    @error('rate_limit_per_day')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-sm">Lưu cấu hình</button>
</form>

@endsection
