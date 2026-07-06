@extends('layouts.backend')
@section('title', 'Passport — ' . ($entry->source_org_name ?? 'Chi tiết'))

@section('content')

{{-- ── Breadcrumb ──────────────────────────────────────────────────────────── --}}
<div class="flex items-center gap-2 text-sm text-base-content/50 mb-4">
    <a href="{{ route('passport.index') }}" class="hover:text-primary">Competency Passport</a>
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-base-content">{{ $entry->source_org_name ?? 'Chi tiết' }}</span>
</div>

{{-- ── Header card ────────────────────────────────────────────────────────── --}}
<div class="card bg-base-100 border border-base-200 shadow-sm mb-5">
    <div class="card-body">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4 min-w-0">
                @if($entry->source_org_logo_path)
                <img src="{{ Storage::url($entry->source_org_logo_path) }}" alt="" class="w-14 h-14 rounded-xl object-cover shrink-0">
                @else
                <div class="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                @endif
                <div>
                    <h1 class="text-xl font-bold text-base-content">{{ $entry->source_org_name ?? 'Tổ chức không xác định' }}</h1>
                    <p class="text-base-content/60">
                        {{ $entry->job_title_at_exit ?? '—' }}
                        @if($entry->department_at_exit) &nbsp;·&nbsp; {{ $entry->department_at_exit }} @endif
                    </p>
                    <p class="text-sm text-base-content/40 mt-1">
                        {{ $entry->tenure_start?->format('d/m/Y') ?? '?' }}
                        →
                        {{ $entry->tenure_end?->format('d/m/Y') ?? '?' }}
                        @if($entry->tenure_months) &nbsp;·&nbsp; {{ $entry->tenure_months }} tháng @endif
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if($entry->org_verified)
                <span class="badge badge-success gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Xác nhận bởi {{ $entry->source_org_name }}
                </span>
                @endif
                @if($entry->has_late_offboard_gap)
                <div class="tooltip" data-tip="Hồ sơ này được xác nhận muộn so với ngày nghỉ thực tế">
                    <span class="badge badge-warning gap-1">⚠ Xác nhận muộn</span>
                </div>
                @endif
                <a href="{{ route('passport.pdf', $entry->uuid) }}" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Tải PDF
                </a>
                <span class="text-xs text-base-content/40">Snapshot: {{ $entry->snapshot_at->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ── Score summary ───────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <div class="stat bg-base-100 border border-base-200 rounded-xl p-4">
        <div class="stat-title text-xs">TDWCF Score</div>
        <div class="stat-value text-primary text-3xl">{{ $entry->tdwcf_score ? number_format($entry->tdwcf_score, 1) : '—' }}</div>
        @if($entry->tdwcf_maturity_level)
        <div class="stat-desc">{{ $entry->tdwcf_maturity_level }}</div>
        @endif
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl p-4">
        <div class="stat-title text-xs">Chứng nhận</div>
        <div class="stat-value text-success text-3xl">{{ $entry->certifications_count }}</div>
        @if($entry->highest_cert_level)
        <div class="stat-desc">Cao nhất: {{ $entry->highest_cert_level }}</div>
        @endif
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl p-4">
        <div class="stat-title text-xs">Giờ Sandbox</div>
        <div class="stat-value text-info text-3xl">{{ $entry->sandbox_hours_total }}h</div>
        @if($entry->sandbox_score_avg)
        <div class="stat-desc">TB: {{ number_format($entry->sandbox_score_avg, 1) }} điểm</div>
        @endif
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl p-4">
        <div class="stat-title text-xs">AI Impact</div>
        <div class="stat-value text-warning text-3xl">{{ $entry->impact_entries_count }}</div>
        <div class="stat-desc">Mục đóng góp</div>
    </div>
</div>

{{-- ── Domain scores + radar ──────────────────────────────────────────────── --}}
@if($entry->domainScores->isNotEmpty())
<div class="card bg-base-100 border border-base-200 shadow-sm mb-5">
    <div class="card-body">
        <h2 class="card-title text-base mb-4">Điểm năng lực 6 Domain</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($entry->domainScores->sortBy('domain_code') as $ds)
            <div class="flex items-center gap-3">
                <span class="badge badge-outline badge-sm w-8 shrink-0 justify-center">{{ $ds->domain_code }}</span>
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm text-base-content/80 truncate">{{ $ds->domain_name }}</span>
                        <span class="text-sm font-semibold ml-2 shrink-0">
                            {{ $ds->score !== null ? number_format($ds->score, 1) : '—' }}
                            @if($ds->required_score)
                            <span class="text-xs font-normal text-base-content/40">/ {{ number_format($ds->required_score, 1) }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="w-full bg-base-200 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full {{ $ds->gap !== null && $ds->gap < 0 ? 'bg-error' : 'bg-primary' }}"
                             style="width: {{ min(100, ($ds->score ?? 0)) }}%"></div>
                    </div>
                    @if($ds->gap !== null && $ds->gap < 0)
                    <p class="text-xs text-error mt-0.5">Gap: {{ number_format($ds->gap, 1) }}</p>
                    @endif
                </div>
                @if($ds->is_critical)
                <span class="badge badge-error badge-xs shrink-0">Critical</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── Certifications ──────────────────────────────────────────────────────── --}}
@if($entry->certifications->isNotEmpty())
<div class="card bg-base-100 border border-base-200 shadow-sm mb-5">
    <div class="card-body">
        <h2 class="card-title text-base mb-4">Chứng nhận đã đạt</h2>
        <div class="flex flex-col gap-2">
            @foreach($entry->certifications as $cert)
            <div class="flex items-center gap-3 p-3 bg-base-50 rounded-lg border border-base-200">
                <div class="w-8 h-8 rounded-lg bg-success/10 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-sm text-base-content">{{ $cert->cert_name }}</p>
                    <p class="text-xs text-base-content/50">{{ $cert->level_code }} · Cấp ngày {{ $cert->issued_at->format('d/m/Y') }}
                        @if($cert->certificate_number) · #{{ $cert->certificate_number }} @endif
                    </p>
                </div>
                @if($cert->isExpired())
                <span class="badge badge-ghost badge-sm">Hết hạn</span>
                @else
                <span class="badge badge-success badge-sm">Còn hiệu lực</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── Impact highlights ───────────────────────────────────────────────────── --}}
@if($entry->impactHighlights->isNotEmpty())
<div class="card bg-base-100 border border-base-200 shadow-sm mb-5">
    <div class="card-body">
        <h2 class="card-title text-base mb-4">AI Impact nổi bật</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($entry->impactHighlights as $impact)
            <div class="p-3 bg-base-50 rounded-lg border border-base-200">
                <p class="text-sm font-medium text-base-content mb-1.5 line-clamp-2">{{ $impact->title }}</p>
                <div class="flex items-end justify-between">
                    <div>
                        @if($impact->improvement_pct)
                        <span class="text-2xl font-bold text-success">+{{ number_format($impact->improvement_pct, 0) }}%</span>
                        <span class="text-xs text-base-content/50 ml-1">cải thiện</span>
                        @endif
                        @if($impact->roi_pct)
                        <p class="text-xs text-base-content/50 mt-0.5">ROI: {{ number_format($impact->roi_pct, 0) }}%</p>
                        @endif
                    </div>
                    @if($impact->period_label)
                    <span class="text-xs text-base-content/40">{{ $impact->period_label }}</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── Sandbox summaries ───────────────────────────────────────────────────── --}}
@if($entry->sandboxSummaries->isNotEmpty())
<div class="card bg-base-100 border border-base-200 shadow-sm mb-5">
    <div class="card-body">
        <h2 class="card-title text-base mb-4">Sandbox AI</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($entry->sandboxSummaries as $sb)
            <div class="p-3 bg-base-50 rounded-lg border border-base-200">
                <p class="text-sm font-medium text-base-content mb-1">{{ $sb->env_name }}</p>
                <div class="flex gap-4 text-sm text-base-content/60">
                    <span>{{ $sb->sessions_completed }} phiên</span>
                    @if($sb->hours_spent) <span>{{ $sb->hours_spent }}h</span> @endif
                    @if($sb->avg_score) <span>TB {{ number_format($sb->avg_score, 1) }} điểm</span> @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── Sharing & personal note ─────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- Share settings --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h2 class="card-title text-base mb-3">Chia sẻ hồ sơ</h2>

            <form action="{{ route('passport.visibility', $entry->uuid) }}" method="POST" class="mb-3">
                @csrf @method('PUT')
                <div class="flex gap-2">
                    <select name="visibility" class="select select-bordered select-sm flex-1">
                        <option value="private" {{ $entry->visibility === 'private' ? 'selected' : '' }}>Riêng tư</option>
                        <option value="link_only" {{ $entry->visibility === 'link_only' ? 'selected' : '' }}>Chia sẻ qua link</option>
                        <option value="public" {{ $entry->visibility === 'public' ? 'selected' : '' }}>Công khai</option>
                    </select>
                    <button class="btn btn-primary btn-sm">Lưu</button>
                </div>
            </form>

            @if($entry->visibility === 'link_only' && $entry->hasValidShareToken())
            <div class="mt-2">
                <label class="label label-text text-xs">Link chia sẻ</label>
                <div class="flex gap-2">
                    <input type="text" readonly
                           value="{{ route('passport.public', $entry->share_token) }}"
                           class="input input-bordered input-xs flex-1 font-mono text-xs"
                           onclick="this.select()">
                    <button onclick="navigator.clipboard.writeText(this.previousElementSibling.value)"
                            class="btn btn-ghost btn-xs">Copy</button>
                </div>
                <p class="text-xs text-base-content/40 mt-1">
                    Hết hạn: {{ $entry->share_token_expires_at?->format('d/m/Y') ?? 'Không giới hạn' }}
                </p>
            </div>
            @endif
        </div>
    </div>

    {{-- Personal note --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h2 class="card-title text-base mb-3">Ghi chú cá nhân</h2>
            <p class="text-xs text-base-content/50 mb-2">Ghi chú này chỉ bạn thấy — không hiện trên trang công khai.</p>
            <form action="{{ route('passport.note', $entry->uuid) }}" method="POST">
                @csrf @method('PUT')
                <textarea name="note" rows="3"
                    class="textarea textarea-bordered w-full text-sm"
                    placeholder="Thêm ghi chú...">{{ $entry->personal_note }}</textarea>
                <button class="btn btn-ghost btn-sm mt-2">Lưu ghi chú</button>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
     class="toast toast-top toast-end">
    <div class="alert alert-success text-sm py-2 px-4">{{ session('success') }}</div>
</div>
@endif

@endsection
