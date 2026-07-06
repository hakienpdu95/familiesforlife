@extends('layouts.backend')
@section('title', 'Competency Passport — Nhật ký Nghề nghiệp')

@section('content')

{{-- ── Header ──────────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Competency Passport</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Nhật ký Nghề nghiệp — lịch sử năng lực tích lũy qua các tổ chức</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('passport.verify.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Xác minh danh tính
        </a>
        @if($user->isOrgMember())
        <a href="{{ route('backend.workforce.me') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Digital Twin hiện tại
        </a>
        @endif
    </div>
</div>

{{-- ── Trust level badge ───────────────────────────────────────────────────── --}}
<div class="card bg-base-100 border border-base-200 shadow-sm mb-6">
    <div class="card-body py-4 flex-row items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-base-content truncate">{{ $user->name }}</p>
            <p class="text-sm text-base-content/50">{{ $user->email }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if($user->trust_level >= 2)
                <span class="badge badge-info gap-1">📱 Điện thoại</span>
            @elseif($user->trust_level >= 1)
                <span class="badge badge-outline gap-1">✉ Email</span>
            @else
                <span class="badge badge-ghost gap-1">Chưa xác minh</span>
            @endif

            @if($user->isFree())
                <span class="badge badge-ghost">Tự do</span>
            @else
                <span class="badge badge-primary">Đang làm việc</span>
            @endif
        </div>
    </div>
</div>

{{-- ── Current chapter (in progress) ─────────────────────────────────────── --}}
@if($currentProfile)
<div class="mb-4">
    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wider mb-2">Chương đang viết</p>
    <div class="card bg-base-100 border-2 border-primary/30 shadow-sm">
        <div class="card-body">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-semibold text-base-content">{{ $currentProfile->organization?->name ?? 'Tổ chức hiện tại' }}</p>
                            <span class="badge badge-primary badge-xs">Đang viết…</span>
                        </div>
                        <p class="text-xs text-base-content/40 mt-0.5">Hồ sơ sẽ được niêm phong khi bạn rời tổ chức này</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 shrink-0">
                    @if($currentProfile->tdwcf_score)
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary">{{ number_format($currentProfile->tdwcf_score, 1) }}</div>
                        <div class="text-xs text-base-content/50">TDWCF</div>
                    </div>
                    @endif
                    @if($currentProfile->certifications_count > 0)
                    <div class="text-center">
                        <div class="text-2xl font-bold text-success">{{ $currentProfile->certifications_count }}</div>
                        <div class="text-xs text-base-content/50">Cert</div>
                    </div>
                    @endif
                    @if($currentProfile->sandbox_hours_total > 0)
                    <div class="text-center">
                        <div class="text-2xl font-bold text-info">{{ $currentProfile->sandbox_hours_total }}h</div>
                        <div class="text-xs text-base-content/50">Sandbox</div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="flex justify-end mt-3 pt-3 border-t border-base-200">
                <a href="{{ route('backend.workforce.me') }}" class="btn btn-ghost btn-xs gap-1">
                    Mở Digital Twin
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ── Empty state (no entries, no current chapter) ───────────────────────── --}}
@if($entries->isEmpty() && !$currentProfile)
<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body items-center text-center py-16">
        <div class="w-16 h-16 rounded-full bg-base-200 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </div>
        <h2 class="text-lg font-bold mb-2">Nhật ký Nghề nghiệp của bạn đang trống</h2>
        <p class="text-base-content/50 text-sm max-w-md">
            Khi bạn rời một tổ chức, hồ sơ năng lực sẽ được tự động lưu thành một chương bất biến tại đây.
        </p>
        <p class="text-base-content/50 text-sm mt-2">Tham gia một tổ chức hoặc tham dự Assessment Marketplace để bắt đầu xây dựng nhật ký.</p>
        <a href="{{ route('campaigns.index') }}" class="btn btn-primary btn-sm mt-4 gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            Khám phá Marketplace
        </a>
    </div>
</div>
@elseif($entries->isEmpty())
<div class="card bg-base-100 border border-dashed border-base-300">
    <div class="card-body items-center text-center py-8">
        <p class="text-base-content/50 text-sm">Chưa có chương nào được niêm phong. Các chương lưu trữ sẽ xuất hiện ở đây khi bạn rời tổ chức hoặc hoàn thành một campaign đánh giá.</p>
    </div>
</div>
@endif

{{-- ── Archived entry list ─────────────────────────────────────────────────── --}}
@if($entries->isNotEmpty())
@if($currentProfile)
<p class="text-xs font-semibold text-base-content/40 uppercase tracking-wider mb-2 mt-4">Các chương đã lưu ({{ $entries->count() }})</p>
@endif
<div class="flex flex-col gap-4">
    @foreach($entries as $entry)
    <div class="card bg-base-100 border border-base-200 shadow-sm hover:shadow-md transition-shadow">
        <div class="card-body">
            <div class="flex flex-wrap items-start justify-between gap-3">

                {{-- Left: org info --}}
                <div class="flex items-center gap-3 min-w-0">
                    @if($entry->source_org_logo_path)
                    <img src="{{ Storage::url($entry->source_org_logo_path) }}" alt="" class="w-10 h-10 rounded-lg object-cover shrink-0">
                    @elseif($entry->entry_type === 'campaign_result')
                    <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    @else
                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    @endif
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-semibold text-base-content">{{ $entry->source_org_name ?? 'Tổ chức không xác định' }}</p>
                            @if($entry->entry_type === 'campaign_result')
                            <span class="badge badge-success badge-xs">Đánh giá Marketplace</span>
                            @endif
                        </div>
                        @if($entry->entry_type === 'campaign_result')
                        <p class="text-sm text-base-content/50">Kết quả đánh giá năng lực số</p>
                        <p class="text-xs text-base-content/40 mt-0.5">{{ $entry->snapshot_at?->format('d/m/Y') }}</p>
                        @else
                        <p class="text-sm text-base-content/50">
                            {{ $entry->job_title_at_exit ?? '—' }}
                            @if($entry->department_at_exit) · {{ $entry->department_at_exit }} @endif
                        </p>
                        <p class="text-xs text-base-content/40 mt-0.5">
                            {{ $entry->tenure_start?->format('m/Y') ?? '?' }}
                            →
                            {{ $entry->tenure_end?->format('m/Y') ?? '?' }}
                            @if($entry->tenure_months) ({{ $entry->tenure_months }} tháng) @endif
                        </p>
                        @endif
                    </div>
                </div>

                {{-- Right: scores --}}
                <div class="flex items-center gap-4 shrink-0">
                    @if($entry->tdwcf_score)
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary">{{ number_format($entry->tdwcf_score, 1) }}</div>
                        <div class="text-xs text-base-content/50">TDWCF</div>
                    </div>
                    @endif
                    @if($entry->certifications_count > 0)
                    <div class="text-center">
                        <div class="text-2xl font-bold text-success">{{ $entry->certifications_count }}</div>
                        <div class="text-xs text-base-content/50">Cert</div>
                    </div>
                    @endif
                    @if($entry->sandbox_hours_total > 0)
                    <div class="text-center">
                        <div class="text-2xl font-bold text-info">{{ $entry->sandbox_hours_total }}h</div>
                        <div class="text-xs text-base-content/50">Sandbox</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Badges row --}}
            <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-base-200">
                @if($entry->org_verified)
                <span class="badge badge-success badge-sm gap-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Xác nhận bởi {{ $entry->source_org_name }}
                </span>
                @endif

                @if($entry->has_late_offboard_gap)
                <span class="badge badge-warning badge-sm">Xác nhận muộn</span>
                @endif

                @if($entry->visibility === 'link_only')
                <span class="badge badge-info badge-sm gap-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    Có link chia sẻ
                </span>
                @elseif($entry->visibility === 'public')
                <span class="badge badge-success badge-sm">Công khai</span>
                @else
                <span class="badge badge-ghost badge-sm">Riêng tư</span>
                @endif

                <div class="ml-auto flex gap-2">
                    <a href="{{ route('passport.show', $entry->uuid) }}" class="btn btn-ghost btn-xs gap-1">
                        Xem chi tiết
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection

