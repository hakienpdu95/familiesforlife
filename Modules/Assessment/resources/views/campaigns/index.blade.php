@extends('layouts.backend')
@section('title', 'Open Assessment Marketplace')

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Open Assessment Marketplace</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Tham gia chiến dịch đánh giá từ các tổ chức, nhận kết quả vào Career Journal</p>
    </div>
</div>

@if($campaigns->isEmpty())
<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body items-center text-center py-16">
        <div class="w-16 h-16 rounded-full bg-base-200 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <h2 class="text-lg font-bold">Chưa có chiến dịch nào</h2>
        <p class="text-base-content/50 text-sm max-w-md">Các tổ chức sẽ đăng chiến dịch đánh giá tại đây. Quay lại sau nhé!</p>
    </div>
</div>
@else
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    @foreach($campaigns as $campaign)
    @php $joined = $myParticipationCampaignIds->has($campaign->id); @endphp
    <div class="card bg-base-100 border border-base-200 shadow-sm hover:shadow-md transition-shadow">
        <div class="card-body">
            {{-- Org + status --}}
            @php $isSelfOrg = $user->current_org_id && $campaign->organization_id === $user->current_org_id; @endphp
            <div class="flex items-start justify-between gap-2 mb-2">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0 text-sm font-bold text-primary">
                        {{ strtoupper(mb_substr($campaign->organization?->name ?? 'O', 0, 1)) }}
                    </div>
                    <span class="text-xs text-base-content/50 truncate">{{ $campaign->organization?->name }}</span>
                    @if($isSelfOrg)
                    <span class="badge badge-ghost badge-xs shrink-0">Tổ chức bạn</span>
                    @endif
                </div>
                <span class="badge {{ $campaign->status->badgeClass() }} badge-sm shrink-0">{{ $campaign->status->label() }}</span>
            </div>

            <h3 class="font-bold text-base-content leading-snug mb-1">{{ $campaign->title }}</h3>

            @if($campaign->target_job_title_label || $campaign->target_department_label)
            <p class="text-xs text-base-content/50 mb-2">
                {{ $campaign->target_job_title_label }}
                @if($campaign->target_department_label) · {{ $campaign->target_department_label }} @endif
            </p>
            @endif

            <p class="text-sm text-base-content/70 line-clamp-2 mb-3">{{ $campaign->description }}</p>

            {{-- Meta chips --}}
            <div class="flex flex-wrap gap-1.5 mb-4">
                <span class="badge badge-outline badge-xs">Lv{{ $campaign->min_trust_level }}+ trust</span>
                <span class="badge badge-outline badge-xs">{{ $campaign->participations_count }} tham gia</span>
                @if($campaign->max_participants)
                <span class="badge badge-outline badge-xs">Tối đa {{ $campaign->max_participants }}</span>
                @endif
                @if($campaign->open_until)
                <span class="badge badge-outline badge-xs">Đến {{ $campaign->open_until->format('d/m/Y') }}</span>
                @endif
                @if($campaign->min_tdwcf_score)
                <span class="badge badge-outline badge-xs">TDWCF ≥ {{ $campaign->min_tdwcf_score }}</span>
                @endif
            </div>

            {{-- Domain requirements --}}
            @if($campaign->domainRequirements->count())
            <div class="flex flex-wrap gap-1 mb-3">
                @foreach($campaign->domainRequirements->take(4) as $req)
                <span class="badge badge-primary badge-xs">{{ $req->domain_code }}</span>
                @endforeach
                @if($campaign->domainRequirements->count() > 4)
                <span class="badge badge-ghost badge-xs">+{{ $campaign->domainRequirements->count() - 4 }}</span>
                @endif
            </div>
            @endif

            <div class="mt-auto flex gap-2">
                <a href="{{ route('campaigns.show', $campaign->uuid) }}" class="btn btn-ghost btn-sm flex-1">Xem chi tiết</a>
                @if($joined)
                <a href="{{ route('campaigns.workspace', $campaign->uuid) }}" class="btn btn-primary btn-sm">Workspace</a>
                @elseif($isSelfOrg)
                {{-- Self-org: no join button, detail page explains why --}}
                @elseif($user->trust_level >= $campaign->min_trust_level)
                <form method="POST" action="{{ route('campaigns.join', $campaign->uuid) }}" class="contents">
                    @csrf
                    <button class="btn btn-success btn-sm">Tham gia</button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-6">{{ $campaigns->links() }}</div>
@endif

@endsection
