@extends('layouts.backend')
@section('title', 'Chi tiết mục tiêu KPI')


@section('content')
<div class="max-w-2xl">
    @if(session('success'))
    <div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
    <div class="alert alert-error mb-4">
        <ul class="text-sm list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Header --}}
    <div class="flex items-start justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold">{{ $goal->title }}</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                {{ $goal->employee?->full_name }} · {{ $goal->cycle_label }}
            </p>
        </div>
        <span class="badge badge-lg {{ $goal->status->badgeClass() }}">{{ $goal->status->label() }}</span>
    </div>

    {{-- Progress card --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body">
            <h2 class="card-title text-base mb-3">Tiến độ</h2>
            <div class="grid grid-cols-3 gap-4 text-center mb-4">
                <div class="bg-base-200 rounded-xl p-3">
                    <div class="text-2xl font-bold tabular-nums">{{ $goal->target_value }}</div>
                    <div class="text-xs text-base-content/50">Mục tiêu{{ $goal->unit ? " ({$goal->unit})" : '' }}</div>
                </div>
                <div class="bg-primary/10 rounded-xl p-3">
                    <div class="text-2xl font-bold text-primary tabular-nums">{{ $goal->current_value }}</div>
                    <div class="text-xs text-base-content/50">Hiện tại</div>
                </div>
                <div class="rounded-xl p-3 {{ (float)$goal->achievement_pct >= 100 ? 'bg-success/10' : 'bg-base-200' }}">
                    <div class="text-2xl font-bold tabular-nums {{ (float)$goal->achievement_pct >= 100 ? 'text-success' : '' }}">
                        {{ number_format((float)$goal->achievement_pct, 1) }}%
                    </div>
                    <div class="text-xs text-base-content/50">Hoàn thành</div>
                </div>
            </div>
            <div class="w-full bg-base-200 rounded-full h-3 mb-1">
                <div class="bg-primary h-3 rounded-full transition-all"
                     style="width: {{ min(100, (float)$goal->achievement_pct) }}%"></div>
            </div>
            <div class="flex justify-between text-xs text-base-content/50">
                <span>0</span>
                <span>Trọng số: <strong>{{ $goal->weight_percent }}%</strong> · Đóng góp: <strong>{{ $goal->weighted_contribution }} điểm</strong></span>
                <span>100%</span>
            </div>
        </div>
    </div>

    {{-- Details --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body">
            <h2 class="card-title text-base mb-3">Thông tin chi tiết</h2>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-base-content/50">Nhân viên</dt>
                    <dd class="font-medium">{{ $goal->employee?->full_name }}</dd>
                </div>
                <div>
                    <dt class="text-base-content/50">Phòng ban</dt>
                    <dd>{{ $goal->employee?->department?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-base-content/50">Kỳ</dt>
                    <dd class="font-mono">{{ $goal->cycle_label }}</dd>
                </div>
                <div>
                    <dt class="text-base-content/50">Thời gian</dt>
                    <dd>{{ $goal->cycle_start?->format('d/m/Y') }} → {{ $goal->cycle_end?->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-base-content/50">Hướng</dt>
                    <dd>{{ $goal->direction->label() }}</dd>
                </div>
                <div>
                    <dt class="text-base-content/50">Loại</dt>
                    <dd>{{ $goal->goal_type->label() }}</dd>
                </div>
                @if($goal->description)
                <div class="col-span-2">
                    <dt class="text-base-content/50">Mô tả</dt>
                    <dd class="mt-1">{{ $goal->description }}</dd>
                </div>
                @endif
                @if($goal->parentGoal)
                <div class="col-span-2">
                    <dt class="text-base-content/50">Mục tiêu cha</dt>
                    <dd><a href="{{ route('backend.kpi.goals.show', $goal->parentGoal) }}" class="link">{{ $goal->parentGoal->title }}</a></dd>
                </div>
                @endif
                @if($goal->approvedBy)
                <div>
                    <dt class="text-base-content/50">Người duyệt</dt>
                    <dd>{{ $goal->approvedBy?->full_name }}</dd>
                </div>
                <div>
                    <dt class="text-base-content/50">Thời gian duyệt</dt>
                    <dd>{{ $goal->approved_at?->format('d/m/Y H:i') }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    @if($goal->snapshot)
    <div class="card bg-success/5 border border-success/20 mb-4">
        <div class="card-body">
            <h2 class="card-title text-base text-success mb-2">Snapshot cuối kỳ</h2>
            <dl class="grid grid-cols-4 gap-3 text-center text-sm">
                <div>
                    <div class="text-xl font-bold tabular-nums">{{ $goal->snapshot->final_value }}</div>
                    <div class="text-xs text-base-content/50">Kết quả cuối</div>
                </div>
                <div>
                    <div class="text-xl font-bold tabular-nums">{{ $goal->snapshot->achievement_pct }}%</div>
                    <div class="text-xs text-base-content/50">Đạt được</div>
                </div>
                <div>
                    <div class="text-xl font-bold tabular-nums">{{ $goal->snapshot->weight_percent }}%</div>
                    <div class="text-xs text-base-content/50">Trọng số</div>
                </div>
                <div>
                    <div class="text-xl font-bold tabular-nums text-success">{{ $goal->snapshot->weighted_score }}</div>
                    <div class="text-xs text-base-content/50">Điểm đóng góp</div>
                </div>
            </dl>
        </div>
    </div>
    @endif

    {{-- AI Analysis --}}
    @can('ai_copilot.use')
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4"
         x-data="aiTask({
             agentSlug:   'kpi.analysis',
             variables:   {
                 employee_name:   '{{ addslashes($goal->employee?->full_name ?? '') }}',
                 cycle_label:     '{{ addslashes($goal->cycle_label) }}',
                 kpi_title:       '{{ addslashes($goal->title) }}',
                 target_value:    '{{ $goal->target_value }}',
                 current_value:   '{{ $goal->current_value }}',
                 achievement_pct: '{{ number_format((float)$goal->achievement_pct, 1) }}',
                 unit:            '{{ addslashes($goal->unit ?? '') }}',
             },
             subjectType: 'Modules\\KpiGoal\\Models\\KpiGoal',
             subjectId:   {{ $goal->id }},
         })">
        <div class="card-body p-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold">✨ AI Phân tích KPI</h2>
                <button type="button" @click="run()" :disabled="loading"
                        class="btn btn-sm btn-outline btn-primary gap-1.5">
                    <svg x-show="!loading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span x-show="loading" class="loading loading-spinner loading-xs"></span>
                    <span x-show="!loading">Phân tích</span>
                    <span x-show="loading">Đang xử lý…</span>
                </button>
            </div>
            <div x-show="error" class="alert alert-error py-2 px-3 text-xs mt-2" x-text="error"></div>
            <div x-show="output" class="mt-3 prose prose-sm max-w-none bg-base-200 rounded-lg p-3 max-h-60 overflow-y-auto whitespace-pre-wrap text-sm" x-text="output"></div>
        </div>
    </div>
    @endcan

    {{-- Actions --}}
    <div class="flex flex-wrap gap-3">
        @if($goal->isDraft())
            @can('approve', $goal)
            <form method="POST" action="{{ route('backend.kpi.goals.approve', $goal) }}">
                @csrf
                <button class="btn btn-success btn-sm">Duyệt → Đang theo dõi</button>
            </form>
            @endcan
        @endif

        @if($goal->isActive())
            @can('updateProgress', $goal)
            <div x-data="{ open: false, val: '{{ $goal->current_value }}' }">
                <button @click="open = true" class="btn btn-primary btn-sm">Cập nhật tiến độ</button>
                <div x-show="open" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center" x-cloak>
                    <div class="card bg-base-100 w-80 shadow-xl">
                        <div class="card-body">
                            <h3 class="card-title text-base">Cập nhật tiến độ</h3>
                            <p class="text-sm text-base-content/60">Mục tiêu: {{ $goal->target_value }} {{ $goal->unit }}</p>
                            <form method="POST" action="{{ route('backend.kpi.goals.progress', $goal) }}">
                                @csrf
                                <div class="form-control mb-3">
                                    <label class="label"><span class="label-text">Giá trị hiện tại</span></label>
                                    <input type="number" name="current_value" x-model="val"
                                           step="any" class="input input-bordered" required>
                                </div>
                                <div class="flex gap-2 justify-end">
                                    <button type="button" @click="open = false" class="btn btn-ghost btn-sm">Hủy</button>
                                    <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endcan
        @endif

        @can('update', $goal)
        <a href="{{ route('backend.kpi.goals.edit', $goal) }}" class="btn btn-ghost btn-sm">Sửa</a>
        @endcan

        <a href="{{ route('backend.kpi.goals.index') }}" class="btn btn-ghost btn-sm">Quay lại</a>
    </div>

    @if($goal->childGoals->isNotEmpty())
    <div class="mt-6">
        <h3 class="font-semibold mb-3">Mục tiêu con (Key Results)</h3>
        <div class="space-y-2">
            @foreach($goal->childGoals as $child)
            <a href="{{ route('backend.kpi.goals.show', $child) }}"
               class="flex items-center justify-between p-3 rounded-lg border border-base-200 hover:bg-base-200 transition-colors">
                <div>
                    <div class="text-sm font-medium">{{ $child->title }}</div>
                    <div class="text-xs text-base-content/50">{{ $child->achievement_pct }}% · W{{ $child->weight_percent }}%</div>
                </div>
                <span class="badge badge-sm {{ $child->status->badgeClass() }}">{{ $child->status->label() }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
