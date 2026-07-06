@extends('layouts.backend')
@section('title', 'Quản lý Plans')

@section('content')
<div x-data="{ confirmDelete: null }">

    {{-- Flash messages --}}
    @foreach(['success','error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition.opacity.duration.500ms
             class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach
    @if ($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Page header --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Plans</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Quản lý các gói dịch vụ và tính năng</p>
        </div>
        <a href="{{ route('subscription.admin.plans.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm plan
        </a>
    </div>

    {{-- Plans grid --}}
    <div class="space-y-4">
        @forelse ($plans as $plan)
        <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
            <div class="card-body p-0">

                {{-- Plan header --}}
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-base-200">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex flex-col min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-base font-bold text-base-content truncate">{{ $plan->name }}</span>
                                <span class="badge badge-ghost badge-sm font-mono">{{ $plan->slug }}</span>
                                @if ($plan->tag_line)
                                    <span class="badge {{ $plan->badge_color ?? 'badge-outline' }} badge-sm">{{ $plan->tag_line }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 mt-1 text-xs text-base-content/50">
                                <span class="capitalize">Tier: {{ $plan->tier }}</span>
                                <span>•</span>
                                <span>
                                    {{ number_format($plan->price) }} {{ $plan->currency }}
                                    @if ($plan->invoice_period > 1)/ {{ $plan->invoice_period }}@endif
                                    / {{ $plan->invoice_interval }}
                                </span>
                                @if ($plan->trial_period > 0)
                                    <span>• Trial {{ $plan->trial_period }}{{ $plan->trial_interval }}</span>
                                @endif
                                <span>• Grace {{ $plan->grace_period }}{{ $plan->grace_interval }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        {{-- Active badge --}}
                        @if ($plan->is_active)
                            <span class="badge badge-success badge-sm">Active</span>
                        @else
                            <span class="badge badge-ghost badge-sm text-base-content/40">Inactive</span>
                        @endif

                        {{-- Toggle --}}
                        <form method="POST" action="{{ route('subscription.admin.plans.toggle', $plan) }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-xs"
                                    title="{{ $plan->is_active ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                @if ($plan->is_active)
                                    <svg class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @endif
                            </button>
                        </form>

                        {{-- Edit --}}
                        <a href="{{ route('subscription.admin.plans.edit', $plan) }}"
                           class="btn btn-ghost btn-xs gap-1" title="Chỉnh sửa">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Sửa
                        </a>

                        {{-- Delete --}}
                        <button @click="confirmDelete = {{ $plan->id }}" class="btn btn-ghost btn-xs text-error" title="Xóa">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Features table --}}
                @if ($plan->features->count() > 0)
                <div class="px-5 py-3 overflow-x-auto">
                    <table class="table table-xs w-full">
                        <thead>
                            <tr class="text-xs text-base-content/50">
                                <th class="font-medium">Feature slug</th>
                                <th class="font-medium">Tên</th>
                                <th class="font-medium text-right">Giá trị</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($plan->features->sortBy('slug') as $feature)
                            <tr>
                                <td class="font-mono text-xs">{{ $feature->slug }}</td>
                                <td class="text-xs text-base-content/70">{{ $feature->name }}</td>
                                <td class="text-right">
                                    @if (in_array($feature->value, ['1', 'true']))
                                        <span class="badge badge-success badge-xs">✓</span>
                                    @elseif (in_array($feature->value, ['0', 'false']))
                                        <span class="badge badge-ghost badge-xs text-base-content/30">✗</span>
                                    @else
                                        <span class="font-mono text-xs font-semibold">{{ $feature->value }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="px-5 py-3 text-sm text-base-content/40">Chưa có features. <a href="{{ route('subscription.admin.plans.edit', $plan) }}" class="link link-primary">Thêm features</a></p>
                @endif

            </div>
        </div>
        @empty
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body text-center py-12">
                <p class="text-base-content/50">Chưa có plan nào.</p>
                <a href="{{ route('subscription.admin.plans.create') }}" class="btn btn-primary btn-sm mt-3">Tạo plan đầu tiên</a>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Confirm delete modal --}}
    <div x-cloak class="modal" :class="{ 'modal-open': confirmDelete !== null }">
        <div class="modal-box max-w-sm">
            <h3 class="font-bold text-base mb-2">Xóa plan?</h3>
            <p class="text-sm text-base-content/70 mb-4">Hành động này không thể hoàn tác. Plan có subscription active sẽ không thể xóa.</p>
            <div class="modal-action gap-2">
                <button @click="confirmDelete = null" class="btn btn-ghost btn-sm">Hủy</button>
                @foreach ($plans as $plan)
                <form x-show="confirmDelete === {{ $plan->id }}" method="POST"
                      action="{{ route('subscription.admin.plans.destroy', $plan) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-error btn-sm">Xóa</button>
                </form>
                @endforeach
            </div>
        </div>
        <div @click="confirmDelete = null" class="modal-backdrop"></div>
    </div>

</div>
@endsection
