@extends('layouts.backend')
@section('title', $campaign->title)

@section('content')

<div class="flex items-center gap-2 text-sm text-base-content/50 mb-4">
    <a href="{{ route('campaigns.index') }}" class="hover:text-primary">Marketplace</a>
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span>{{ $campaign->title }}</span>
</div>

@if(session('success'))
<div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
@endif

@error('join')
<div class="alert alert-error mb-4"><span>{{ $message }}</span></div>
@else
{{-- Show eligibility block reason on page load (before any join attempt) --}}
@if($eligibility && !$eligibility->canJoin)
<div class="alert alert-error mb-4">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <div>
        <span>{{ $eligibility->block->message }}</span>
    </div>
</div>
@endif
@enderror

{{-- Cross-org advisory (non-blocking, informational) --}}
@if($eligibility)
@foreach($eligibility->advisories as $advisory)
<div class="alert alert-{{ $advisory->severity === 'warning' ? 'warning' : 'info' }} mb-4">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <div>
        <span>{{ $advisory->message }}</span>
        @if($advisory->actionUrl)
        <a href="{{ $advisory->actionUrl }}" class="link link-hover font-medium ml-1">{{ $advisory->actionLabel }}</a>
        @endif
    </div>
</div>
@endforeach
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

  {{-- Main info --}}
  <div class="lg:col-span-2 space-y-5">

    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="badge {{ $campaign->status->badgeClass() }}">{{ $campaign->status->label() }}</span>
                        @if($campaign->is_anonymous_to_org)
                        <span class="badge badge-ghost badge-sm">🔒 Ẩn danh</span>
                        @endif
                    </div>
                    <h1 class="text-2xl font-bold mb-1">{{ $campaign->title }}</h1>
                    <p class="text-base-content/60">{{ $campaign->organization?->name }}</p>
                    @if($campaign->target_job_title_label)
                    <p class="text-sm text-base-content/50 mt-1">
                        Vị trí: {{ $campaign->target_job_title_label }}
                        @if($campaign->target_department_label) · {{ $campaign->target_department_label }} @endif
                    </p>
                    @endif
                </div>

                @if($myParticipation)
                    @if($myParticipation->status->value === 'completed')
                    <div class="badge badge-success badge-lg">✓ Đã hoàn thành</div>
                    @elseif($myParticipation->status->value === 'in_progress')
                    <a href="{{ route('campaigns.workspace', $campaign->uuid) }}" class="btn btn-primary gap-1.5">
                        Vào Workspace
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                    @elseif($myParticipation->status->value === 'declined')
                    <div class="badge badge-warning badge-lg">Đã từ chối</div>
                    @endif
                @elseif($eligibility && $eligibility->canJoin)
                <form method="POST" action="{{ route('campaigns.join', $campaign->uuid) }}">
                    @csrf
                    <button class="btn btn-success gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        Tham gia ngay
                    </button>
                </form>
                @elseif($eligibility && !$eligibility->canJoin && $eligibility->block->actionUrl)
                <a href="{{ $eligibility->block->actionUrl }}" class="btn btn-warning btn-sm">
                    {{ $eligibility->block->actionLabel }}
                </a>
                @endif
            </div>

            @if($campaign->description)
            <div class="divider"></div>
            <p class="text-base-content/80 leading-relaxed">{{ $campaign->description }}</p>
            @endif
        </div>
    </div>

    {{-- Sandbox tasks --}}
    @if($campaign->sandboxTasks->count())
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h2 class="font-semibold mb-3">Task thực hành (Sandbox)</h2>
            <div class="space-y-3">
                @foreach($campaign->sandboxTasks as $ct)
                <div class="flex items-start gap-3 p-3 bg-base-200/50 rounded-lg">
                    <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center text-xs font-bold text-primary shrink-0 mt-0.5">
                        {{ $loop->iteration }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="font-medium text-sm">{{ $ct->task?->title ?? 'Task #'.$ct->sandbox_task_id }}</p>
                            @if($ct->is_required)
                            <span class="badge badge-error badge-xs">Bắt buộc</span>
                            @else
                            <span class="badge badge-ghost badge-xs">Tuỳ chọn</span>
                            @endif
                        </div>
                        @if($ct->task?->time_limit_minutes)
                        <p class="text-xs text-base-content/50 mt-0.5">⏱ {{ $ct->task->time_limit_minutes }} phút</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Invite status --}}
    @if($myParticipation && $myParticipation->isInvited())
    <div class="card bg-success/10 border border-success/30 shadow-sm">
        <div class="card-body">
            <h2 class="font-semibold text-success mb-2">🎉 Bạn nhận được lời mời!</h2>
            <p class="text-sm text-base-content/70 mb-4">
                <strong>{{ $campaign->organization?->name }}</strong> muốn mời bạn tham gia phỏng vấn.
                @if($myParticipation->org_note)
                <br>Ghi chú: "{{ $myParticipation->org_note }}"
                @endif
            </p>
            <form method="PATCH" action="{{ route('campaigns.decline', $campaign->uuid) }}">
                @csrf
                @method('PATCH')
                <button class="btn btn-warning btn-sm">Từ chối lời mời</button>
            </form>
        </div>
    </div>
    @endif

  </div>

  {{-- Sidebar --}}
  <div class="space-y-4">

    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold mb-3">Thông tin tham gia</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-base-content/50">Trust level tối thiểu</dt>
                    <dd class="font-medium">Lv{{ $campaign->min_trust_level }}</dd>
                </div>
                @if($campaign->min_tdwcf_score)
                <div class="flex justify-between">
                    <dt class="text-base-content/50">TDWCF tối thiểu</dt>
                    <dd class="font-medium">{{ $campaign->min_tdwcf_score }}</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-base-content/50">Đã tham gia</dt>
                    <dd class="font-medium">{{ $campaign->participants_count }}@if($campaign->max_participants) / {{ $campaign->max_participants }}@endif</dd>
                </div>
                @if($campaign->open_from)
                <div class="flex justify-between">
                    <dt class="text-base-content/50">Mở từ</dt>
                    <dd class="font-medium">{{ $campaign->open_from->format('d/m/Y') }}</dd>
                </div>
                @endif
                @if($campaign->open_until)
                <div class="flex justify-between">
                    <dt class="text-base-content/50">Hạn nộp</dt>
                    <dd class="font-medium text-warning">{{ $campaign->open_until->format('d/m/Y') }}</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-base-content/50">Kết quả của org</dt>
                    <dd class="font-medium">{{ $campaign->is_anonymous_to_org ? '🔒 Ẩn danh' : 'Hiển thị tên' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Domain requirements --}}
    @if($campaign->domainRequirements->count())
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold mb-3">Yêu cầu năng lực</h3>
            @php
              $domainNames = ['D1'=>'Digital','D2'=>'Data','D3'=>'AI','D4'=>'Workflow','D5'=>'Innovation','D6'=>'Performance'];
            @endphp
            <div class="space-y-2">
                @foreach($campaign->domainRequirements as $req)
                <div class="flex items-center justify-between">
                    <span class="text-sm">{{ $req->domain_code }} — {{ $domainNames[$req->domain_code] ?? '' }}</span>
                    <span class="badge badge-outline badge-xs">≥ {{ $req->min_score }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- My result --}}
    @if($myParticipation && $myParticipation->isCompleted())
    <div class="card bg-success/5 border border-success/30 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold mb-3 text-success">Kết quả của bạn</h3>
            <div class="text-center">
                <div class="text-3xl font-bold text-primary">{{ $myParticipation->result_tdwcf_score }}</div>
                <div class="text-xs text-base-content/50 mt-1">TDWCF Score</div>
                @if($myParticipation->result_maturity_level)
                <div class="badge badge-primary mt-2">{{ $myParticipation->result_maturity_level }}</div>
                @endif
            </div>
            @if($myParticipation->passport_entry_id)
            <a href="{{ route('passport.index') }}" class="btn btn-ghost btn-sm mt-3 w-full">Xem trong Passport</a>
            @endif
        </div>
    </div>
    @endif

  </div>

</div>

@endsection
