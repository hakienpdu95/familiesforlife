@extends('layouts.backend')
@section('title', $workflow->name)


@section('content')
@php
$workflow->load(['triggerParams', 'conditions', 'steps.headers']);
$lastStatus = $workflow->last_run_status_enum;
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold text-base-content">{{ $workflow->name }}</h1>
            @if($workflow->is_active)
                <span class="badge badge-success badge-sm">Đang bật</span>
            @else
                <span class="badge badge-ghost badge-sm">Đang tắt</span>
            @endif
        </div>
        @if($workflow->description)
        <p class="text-sm text-base-content/50 mt-0.5">{{ $workflow->description }}</p>
        @endif
    </div>
    <div class="flex gap-2">
        <a href="{{ route('workflows.executions', $workflow) }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Lịch sử chạy
        </a>
        @can(\App\Enums\PermissionEnum::WORKFLOW_EDIT->value)
        <a href="{{ route('workflows.edit', $workflow) }}" class="btn btn-ghost btn-sm">Sửa</a>
        <button id="btnToggle" onclick="wfToggle({{ $workflow->id }}, this)"
                class="btn btn-sm {{ $workflow->is_active ? 'btn-warning' : 'btn-success' }}">
            {{ $workflow->is_active ? 'Tắt workflow' : 'Bật workflow' }}
        </button>
        <button onclick="wfManualRun({{ $workflow->id }}, this)"
                class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Chạy thủ công
        </button>
        @endcan
    </div>
</div>

{{-- ── Flash ─────────────────────────────────────────────────────────────────── --}}
@foreach(['success','info','error'] as $t)
@if(session($t))
<div class="alert alert-{{ $t }} text-sm py-2 px-4 rounded-lg mb-4">{{ session($t) }}</div>
@endif
@endforeach
<div id="wfAlert" class="hidden alert text-sm py-2 px-4 rounded-lg mb-4"></div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- ── Left: Stat cards ─────────────────────────────────────────────────── --}}
    <div class="lg:col-span-1 space-y-4">

        <div class="stats stats-vertical shadow-sm border border-base-200 w-full">
            <div class="stat py-3 px-4">
                <div class="stat-title text-xs">Tổng lần chạy</div>
                <div class="stat-value text-2xl">{{ number_format($workflow->run_count) }}</div>
            </div>
            <div class="stat py-3 px-4">
                <div class="stat-title text-xs">Trạng thái gần nhất</div>
                <div class="stat-value text-xl">
                    @if($lastStatus)
                        <span class="badge badge-soft {{ $lastStatus->badge() }}">{{ $lastStatus->label() }}</span>
                    @else
                        <span class="text-base-content/30 text-sm">Chưa chạy</span>
                    @endif
                </div>
            </div>
            <div class="stat py-3 px-4">
                <div class="stat-title text-xs">Lần chạy gần nhất</div>
                <div class="stat-value text-base font-normal">
                    {{ $workflow->last_run_at ? $workflow->last_run_at->format('d/m/Y H:i') : '—' }}
                </div>
            </div>
            <div class="stat py-3 px-4">
                <div class="stat-title text-xs">Ưu tiên</div>
                <div class="stat-value text-2xl">{{ $workflow->priority }}</div>
            </div>
        </div>

        {{-- Trigger params --}}
        @if($workflow->triggerParams->isNotEmpty())
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-3 px-4">
                <h3 class="font-semibold text-sm mb-2">Cấu hình Trigger</h3>
                <dl class="space-y-1.5">
                    @foreach($workflow->triggerParams as $p)
                    <div class="flex justify-between text-sm">
                        <dt class="text-base-content/50 font-mono text-xs">{{ $p->param_key }}</dt>
                        <dd class="font-medium font-mono text-xs">{{ $p->param_value }}</dd>
                    </div>
                    @endforeach
                </dl>
            </div>
        </div>
        @endif

    </div>

    {{-- ── Right: Details ────────────────────────────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Overview card --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-3 px-4">
                <h3 class="font-semibold text-sm mb-3">Tổng quan</h3>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                    <div><dt class="text-xs text-base-content/40 uppercase tracking-wide mb-0.5">Trigger</dt>
                         <dd class="font-mono text-xs font-medium">{{ $workflow->trigger_type }}</dd></div>
                    <div><dt class="text-xs text-base-content/40 uppercase tracking-wide mb-0.5">Khớp điều kiện</dt>
                         <dd>{{ $workflow->condition_match_enum->name }}</dd></div>
                    <div><dt class="text-xs text-base-content/40 uppercase tracking-wide mb-0.5">Cooldown</dt>
                         <dd>{{ $workflow->cooldown_type_enum->label() }}</dd></div>
                    <div><dt class="text-xs text-base-content/40 uppercase tracking-wide mb-0.5">Ngày tạo</dt>
                         <dd>{{ $workflow->created_at->format('d/m/Y') }}</dd></div>
                </dl>
            </div>
        </div>

        {{-- Conditions --}}
        @if($workflow->conditions->isNotEmpty())
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-3 px-4">
                <h3 class="font-semibold text-sm mb-3">Điều kiện ({{ $workflow->conditions->count() }})</h3>
                <div class="space-y-1.5">
                    @foreach($workflow->conditions as $c)
                    <div class="flex items-center gap-2 text-sm">
                        <span class="badge badge-ghost badge-sm font-mono">{{ $c->field }}</span>
                        <span class="text-base-content/50 text-xs">{{ $c->operator }}</span>
                        <span class="font-medium font-mono text-xs">{{ $c->value }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Steps --}}
        @if($workflow->steps->isNotEmpty())
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-3 px-4">
                <h3 class="font-semibold text-sm mb-3">Steps ({{ $workflow->steps->count() }})</h3>
                <div class="space-y-2">
                    @foreach($workflow->steps as $step)
                    <div class="flex items-start gap-3 p-3 bg-base-200/50 rounded-lg">
                        <span class="badge badge-ghost badge-sm shrink-0 mt-0.5">{{ $loop->iteration }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-sm font-mono">{{ $step->action_type }}</p>
                            @if($step->delay_minutes > 0)
                            <p class="text-xs text-base-content/40 mt-0.5">Delay: {{ $step->delay_minutes }} phút</p>
                            @endif
                            @if($step->email_to)
                            <p class="text-xs text-base-content/60 mt-0.5">To: {{ $step->email_to }} | Subject: {{ $step->email_subject }}</p>
                            @endif
                            @if($step->notif_target)
                            <p class="text-xs text-base-content/60 mt-0.5">Target: {{ $step->notif_target }} | {{ $step->notif_title }}</p>
                            @endif
                            @if($step->webhook_url)
                            <p class="text-xs text-base-content/60 mt-0.5 truncate">URL: {{ $step->webhook_url }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

    </div>
</div>

<script>
var CSRF_TOKEN = '{{ csrf_token() }}';

function wfShowAlert(msg, type) {
    var el = document.getElementById('wfAlert');
    el.className = 'alert alert-' + type + ' text-sm py-2 px-4 rounded-lg mb-4';
    el.textContent = msg;
    el.classList.remove('hidden');
    setTimeout(function () { el.classList.add('hidden'); }, 5000);
}

window.wfToggle = async function (id, btn) {
    btn.disabled = true;
    try {
        var res = await fetch('/dashboard/workflows/' + id + '/toggle', {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        });
        var data = await res.json();
        if (res.ok) {
            btn.textContent = data.is_active ? 'Tắt workflow' : 'Bật workflow';
            btn.className   = 'btn btn-sm ' + (data.is_active ? 'btn-warning' : 'btn-success');
        }
    } catch (e) { wfShowAlert('Lỗi kết nối', 'error'); }
    finally { btn.disabled = false; }
};

window.wfManualRun = async function (id, btn) {
    btn.disabled = true;
    try {
        var res = await fetch('/dashboard/workflows/' + id + '/run', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        });
        var data = await res.json();
        if (res.ok) {
            wfShowAlert('Đã đưa vào hàng chờ (run_id: ' + data.run_id + ')', 'success');
        } else {
            wfShowAlert(data.message || 'Lỗi chạy thủ công', 'error');
        }
    } catch (e) { wfShowAlert('Lỗi kết nối', 'error'); }
    finally { btn.disabled = false; }
};
</script>
@endsection
