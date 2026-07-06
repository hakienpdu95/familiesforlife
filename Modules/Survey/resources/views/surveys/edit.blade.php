@extends('layouts.backend')
@section('title', 'Chỉnh sửa: ' . $survey->title)


@section('content')

{{-- Page header --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $survey->title }}</h1>
        <div class="flex items-center gap-2 mt-1">
            <span class="badge badge-sm {{ $survey->status->badgeClass() }}">{{ $survey->status->label() }}</span>
            <span class="text-xs text-base-content/40 font-mono">/surveys/{{ $survey->slug }}</span>
            <span class="text-xs text-base-content/40">· v{{ $survey->version }}</span>
        </div>
    </div>

    <div class="flex items-center gap-2">
        {{-- Activate button (Draft only) --}}
        @can('survey.update')
        @if($survey->status->value === 0)
        <form method="POST" action="{{ route('backend.surveys.activate', $survey) }}"
              onsubmit="return confirm('Kích hoạt survey? Sau khi active, survey sẽ nhận phản hồi.')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Kích hoạt
            </button>
        </form>
        @endif
        @endcan

        <a href="{{ route('backend.surveys.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Quay lại
        </a>
    </div>
</div>

{{-- Error banner --}}
@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ── Left: General info + Stats ──────────────────────────────────── --}}
    <div class="lg:col-span-1 space-y-4">

        {{-- Thông tin chung --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Thông tin chung
                </h2>

                <form method="POST" action="{{ route('backend.surveys.update', $survey) }}"
                      novalidate data-survey-form>
                    @csrf @method('PUT')

                    {{-- Tổ chức --}}
                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium text-sm">Tổ chức <span class="text-error">*</span></span>
                        </label>
                        @if($orgLocked)
                            <input type="hidden" name="organization_id" value="{{ $organizations->first()->id }}">
                            <input type="text" value="{{ $organizations->first()->name }}" readonly
                                   class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
                            <p class="mt-1 text-xs text-base-content/40">Xác định từ tài khoản của bạn.</p>
                        @else
                            <select id="ts-organization" name="organization_id"
                                    class="select select-bordered select-sm w-full ts-init @error('organization_id') select-error @enderror"
                                    data-ts-placeholder="— Chọn tổ chức —"
                                    data-req="Vui lòng chọn tổ chức">
                                <option value="">— Chọn tổ chức —</option>
                                @foreach($organizations as $org)
                                <option value="{{ $org->id }}" {{ old('organization_id', $survey->organization_id) == $org->id ? 'selected' : '' }}>
                                    {{ $org->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        @endif
                    </div>

                    {{-- Tiêu đề --}}
                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium text-sm">Tiêu đề <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="title"
                               value="{{ old('title', $survey->title) }}"
                               data-req="Vui lòng nhập tiêu đề"
                               class="input input-bordered input-sm w-full @error('title') input-error @enderror">
                        @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Mô tả --}}
                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium text-sm">Mô tả</span>
                        </label>
                        <textarea name="description" rows="3"
                                  class="textarea textarea-bordered w-full text-sm @error('description') textarea-error @enderror"
                                  placeholder="Mô tả ngắn về mục đích hoặc nội dung khảo sát...">{{ old('description', $survey->description) }}</textarea>
                        @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Slug (read-only) --}}
                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium text-sm">Slug</span>
                            <span class="label-text-alt text-xs text-base-content/40">Hệ thống tự sinh</span>
                        </label>
                        <input type="text" value="{{ $survey->slug }}" disabled
                               class="input input-bordered input-sm w-full field-readonly">
                    </div>

                    {{-- Version --}}
                    <div class="form-control mb-5">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium text-sm">Version</span>
                        </label>
                        <input type="number" name="version"
                               value="{{ old('version', $survey->version) }}"
                               min="1" max="9999"
                               class="input input-bordered input-sm w-full @error('version') input-error @enderror">
                        @error('version')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Turnstile site --}}
                    <div class="form-control mb-5">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium text-sm">Turnstile site</span>
                        </label>
                        <select name="turnstile_site_id"
                                class="select select-bordered select-sm w-full @error('turnstile_site_id') select-error @enderror">
                            <option value="">— Không dùng Turnstile —</option>
                            @foreach($turnstileSites as $site)
                            <option value="{{ $site->id }}" {{ old('turnstile_site_id', $survey->turnstile_site_id) == $site->id ? 'selected' : '' }}>
                                {{ $site->name }}
                            </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-base-content/40">
                            Domain sẽ nhúng form này.
                            <a href="{{ route('backend.turnstile-sites.index') }}" class="link link-primary">Quản lý sites</a>
                        </p>
                        @error('turnstile_site_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Meta timestamps --}}
                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $survey->created_at->format('d/m/Y') }}</span>
                        <span>Sửa {{ $survey->updated_at->diffForHumans() }}</span>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-full gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Lưu lại
                    </button>

                </form>
            </div>
        </div>

        {{-- Thống kê nhanh + điều hướng --}}
        <div class="card bg-base-100 shadow-sm border border-base-200"
             x-data="{
                 totalResponses: {{ $survey->responses()->complete()->count() }},
                 totalSections:  {{ $survey->sections->count() }},
                 activeFields:   {{ $survey->sections->sum(fn($s) => $s->fields->where('is_active', true)->count()) }}
             }"
             @survey-stats-updated.window="
                 totalSections = $event.detail.sections;
                 activeFields  = $event.detail.activeFields;
             ">
            <div class="card-body p-5">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Thống kê nhanh
                </h2>

                <div class="space-y-2 text-sm mb-4">
                    <div class="flex justify-between">
                        <span class="text-base-content/60">Tổng responses</span>
                        <span class="font-semibold" x-text="totalResponses"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/60">Số sections</span>
                        <span class="font-semibold" x-text="totalSections"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/60">Fields active</span>
                        <span class="font-semibold" x-text="activeFields"></span>
                    </div>
                </div>

                @can('survey.view_responses')
                <div class="divider my-3 text-xs text-base-content/30">Dữ liệu</div>
                <div class="flex flex-col gap-2">
                    <div class="flex gap-2">
                        <a href="{{ route('backend.surveys.responses.index', $survey) }}"
                           class="btn btn-ghost btn-xs flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Responses
                        </a>
                        <a href="{{ route('backend.surveys.stats.index', $survey) }}"
                           class="btn btn-ghost btn-xs flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Thống kê
                        </a>
                    </div>
                    @can('survey.export')
                    <a href="{{ route('backend.surveys.responses.export', $survey) }}"
                       class="btn btn-ghost btn-xs w-full gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export Excel
                    </a>
                    @endcan
                </div>
                @endcan

                @can('survey.update')
                <div class="divider my-3 text-xs text-base-content/30">Cấu hình</div>
                <a href="{{ route('backend.surveys.scoring.index', $survey) }}"
                   class="btn btn-outline btn-sm w-full gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Cấu hình Scoring
                    @if($survey->assessment_code)
                    <span class="badge badge-sm badge-success">✓</span>
                    @endif
                </a>
                @endcan

                @can('survey.manage_tokens')
                <div class="divider my-3 text-xs text-base-content/30"></div>
                <a href="{{ route('backend.surveys.tokens.index', $survey) }}"
                   class="btn btn-outline btn-sm w-full gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    API Tokens
                    <span class="badge badge-sm badge-ghost">{{ $survey->tokens()->active()->count() }}</span>
                </a>
                <a href="{{ route('backend.turnstile-sites.index') }}"
                   class="btn btn-outline btn-sm w-full gap-2 mt-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Turnstile Sites
                </a>
                @endcan

            </div>
        </div>

    </div>

    {{-- ── Right: Survey Builder ────────────────────────────────────────── --}}
    <div class="lg:col-span-2">
        @include('survey::builder.index')
    </div>

</div>

@endsection

@push('styles')
    @vite(['Modules/Survey/resources/assets/sass/survey.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite(['resources/js/modules/tom-select.js'], 'build/backend')
    @vite([
        'Modules/Survey/resources/assets/js/survey.js',
    ], 'build/backend')
@endpush
