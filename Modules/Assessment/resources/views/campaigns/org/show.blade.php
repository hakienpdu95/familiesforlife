@extends('layouts.backend')
@section('title', $campaign->title . ' — Chi tiết')

@section('content')

<div class="flex items-center gap-2 text-sm text-base-content/50 mb-4">
    <a href="{{ route('campaigns.admin.index') }}" class="hover:text-primary">Campaigns</a>
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span>{{ $campaign->title }}</span>
</div>

@if(session('success'))
<div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

  <div class="lg:col-span-2 space-y-5">

    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <span class="badge {{ $campaign->status->badgeClass() }} mb-2">{{ $campaign->status->label() }}</span>
                    <h1 class="text-xl font-bold">{{ $campaign->title }}</h1>
                    @if($campaign->target_job_title_label)
                    <p class="text-base-content/60 text-sm mt-1">{{ $campaign->target_job_title_label }}
                    @if($campaign->target_department_label) · {{ $campaign->target_department_label }} @endif</p>
                    @endif
                    @if($campaign->description)
                    <p class="text-base-content/70 mt-3">{{ $campaign->description }}</p>
                    @endif
                </div>
                <a href="{{ route('campaigns.admin.results', $campaign->uuid) }}" class="btn btn-primary btn-sm">
                    Xem kết quả →
                </a>
            </div>
        </div>
    </div>

    {{-- Domain requirements --}}
    @if($campaign->domainRequirements->count())
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h2 class="font-semibold mb-3">Yêu cầu năng lực</h2>
            <div class="flex flex-wrap gap-2">
                @php $names = ['D1'=>'Digital','D2'=>'Data','D3'=>'AI','D4'=>'Workflow','D5'=>'Innovation','D6'=>'Performance']; @endphp
                @foreach($campaign->domainRequirements as $req)
                <div class="badge badge-outline gap-1">
                    <span class="font-bold">{{ $req->domain_code }}</span>
                    {{ $names[$req->domain_code] ?? '' }} ≥ {{ $req->min_score }}
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Sandbox tasks --}}
    @if($campaign->sandboxTasks->count())
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h2 class="font-semibold mb-3">Task Sandbox ({{ $campaign->sandboxTasks->count() }})</h2>
            <div class="space-y-2">
                @foreach($campaign->sandboxTasks as $ct)
                <div class="flex items-center gap-3 p-3 bg-base-200/40 rounded-lg">
                    <span class="text-sm font-bold text-base-content/40">{{ $loop->iteration }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium">{{ $ct->task?->title ?? 'Task #'.$ct->sandbox_task_id }}</p>
                        @if($ct->task?->time_limit_minutes)
                        <p class="text-xs text-base-content/50">⏱ {{ $ct->task->time_limit_minutes }} phút</p>
                        @endif
                    </div>
                    @if($ct->is_required)
                    <span class="badge badge-error badge-xs">Bắt buộc</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

  </div>

  {{-- Sidebar --}}
  <div class="space-y-4">

    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold mb-3">Thống kê</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-base-content/50">Đã tham gia</dt>
                    <dd class="font-bold text-lg">{{ $campaign->participants_count }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-base-content/50">Hoàn thành</dt>
                    <dd class="font-bold text-lg text-success">{{ $campaign->completed_count }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-base-content/50">Trust LV tối thiểu</dt>
                    <dd class="font-medium">Lv{{ $campaign->min_trust_level }}</dd>
                </div>
                @if($campaign->max_participants)
                <div class="flex justify-between">
                    <dt class="text-base-content/50">Giới hạn</dt>
                    <dd class="font-medium">{{ $campaign->max_participants }}</dd>
                </div>
                @endif
                @if($campaign->open_until)
                <div class="flex justify-between">
                    <dt class="text-base-content/50">Hạn nộp</dt>
                    <dd class="font-medium">{{ $campaign->open_until->format('d/m/Y') }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    {{-- Status change --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold mb-3">Đổi trạng thái</h3>
            <form method="POST" action="{{ route('campaigns.admin.status', $campaign->uuid) }}">
                @csrf
                @method('PATCH')
                <div class="flex gap-2">
                    <select name="status" class="select select-bordered select-sm flex-1">
                        @foreach(['draft'=>'Nháp','open'=>'Mở','closed'=>'Đóng','archived'=>'Lưu trữ'] as $val => $label)
                        <option value="{{ $val }}" {{ $campaign->status->value === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-outline">Lưu</button>
                </div>
            </form>
        </div>
    </div>

  </div>

</div>

@endsection
