@extends('layouts.backend')
@section('title', $sop->code . ' — Ma trận RACI')


@section('content')
<div class="mb-5 flex items-center gap-3">
    <div>
        <h1 class="text-xl font-bold text-base-content">Ma trận RACI</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $sop->code }} — {{ $sop->title }}</p>
    </div>
    <div class="ml-auto flex gap-2">
        <a href="{{ route('backend.sop.show', $sop) }}?tab=flowchart" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Quay lại
        </a>
    </div>
</div>

{{-- Legend --}}
<div class="flex flex-wrap gap-3 mb-5 text-xs">
    @foreach(['R' => ['Thực hiện (Responsible)', 'bg-blue-50 text-blue-700 border-blue-200'], 'A' => ['Chịu trách nhiệm (Accountable)', 'bg-amber-50 text-amber-700 border-amber-200'], 'C' => ['Tư vấn (Consulted)', 'bg-teal-50 text-teal-700 border-teal-200'], 'I' => ['Thông báo (Informed)', 'bg-gray-100 text-gray-600 border-gray-200']] as $type => [$desc, $cls])
    <span class="flex items-center gap-1.5 px-2.5 py-1 rounded border {{ $cls }} font-medium">
        <strong>{{ $type }}</strong> — {{ $desc }}
    </span>
    @endforeach
</div>

@if($steps->isEmpty())
<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body py-14 flex flex-col items-center text-base-content/30">
        <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
        </svg>
        <p class="text-sm font-medium">Quy trình chưa có bước nào</p>
    </div>
</div>
@elseif($assignees->isEmpty())
<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body py-14 flex flex-col items-center text-base-content/30">
        <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <p class="text-sm font-medium">Chưa có phân công RACI nào</p>
        <p class="text-xs mt-1">Mở Flowchart và click vào từng bước để thêm RACI</p>
        @can('update', $sop)
        <a href="{{ route('backend.sop.show', $sop) }}?tab=flowchart" class="btn btn-primary btn-sm mt-3 gap-1.5">
            Đi đến Flowchart
        </a>
        @endcan
    </div>
</div>
@else
<div class="card bg-base-100 border border-base-200 shadow-sm overflow-x-auto">
    <table class="table table-sm text-xs w-full">
        <thead>
            <tr class="bg-base-200/50">
                <th class="sticky left-0 bg-base-200/50 z-10 min-w-40 font-semibold text-base-content/60 uppercase tracking-wide py-3">
                    Người / Vai trò
                </th>
                @foreach($steps as $step)
                <th class="text-center font-normal min-w-24 py-3">
                    <div class="font-medium text-base-content/70 truncate max-w-24" title="{{ $step->title }}">{{ $step->title }}</div>
                    <div class="text-base-content/30 font-normal">B{{ $step->position }}</div>
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($assignees as $assignee)
            <tr class="hover:bg-base-50 border-b border-base-100">
                <td class="sticky left-0 bg-base-100 z-10 font-medium text-base-content/80 py-2.5 pr-3">
                    {{ $assignee['name'] }}
                </td>
                @foreach($steps as $step)
                @php $types = $assignee['by_step']->get($step->id, []); @endphp
                <td class="text-center py-2.5">
                    @if(count($types) > 0)
                    <div class="flex flex-wrap gap-0.5 justify-center">
                        @foreach($types as $type)
                        @php
                        $cls = match($type) {
                            'R' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'A' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'C' => 'bg-teal-50 text-teal-700 border-teal-200',
                            'I' => 'bg-gray-100 text-gray-600 border-gray-200',
                            default => 'bg-base-100 text-base-content/40 border-base-200',
                        };
                        @endphp
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded border text-xs font-bold {{ $cls }}">{{ $type }}</span>
                        @endforeach
                    </div>
                    @else
                    <span class="text-base-content/15">—</span>
                    @endif
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Warning: steps without R --}}
@php
$stepsWithoutR = $steps->filter(function ($step) use ($assignees) {
    if (!in_array($step->step_type, ['action', 'decision'])) return false;
    foreach ($assignees as $a) {
        if (in_array('R', $a['by_step']->get($step->id, []))) return false;
    }
    return true;
});
@endphp
@if($stepsWithoutR->isNotEmpty())
<div class="alert alert-warning mt-4 text-sm py-3">
    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.07 16.5c-.77.833.192 2.5 1.732 2.5z"/>
    </svg>
    <div>
        <p class="font-medium">Các bước sau chưa có người Thực hiện (R):</p>
        <ul class="list-disc list-inside mt-1 text-xs space-y-0.5">
            @foreach($stepsWithoutR as $step)
            <li>Bước {{ $step->position }}: {{ $step->title }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif
@endif

@endsection
