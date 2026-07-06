@extends('layouts.backend')
@section('title', 'Nhật ký tiến độ')

@push('styles')
    @vite(['Modules/Deployment/resources/assets/sass/deployment.scss'], 'build/backend')
@endpush

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Nhật ký tiến độ</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $vertical->label() }}</p>
    </div>
</div>

{{-- Flash: success --}}
@if(session('success'))
<div class="flex items-center gap-3 bg-success/10 border border-success/30 rounded-lg py-3 px-4 mb-5 text-sm text-success">
    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
    </svg>
    {{ session('success') }}
</div>
@endif

{{-- Flash: error --}}
@if($errors->any())
<div class="flex items-start gap-3 bg-error/10 border border-error/30 rounded-lg py-3 px-4 mb-5 text-sm text-error">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-[340px_1fr] gap-6 items-start">

    {{-- Log form --}}
    <div class="lg:sticky lg:top-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5 gap-2">
                    <svg class="w-4 h-4 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Ghi nhận tiến độ
                </h2>

                <form method="POST"
                      action="{{ route('deployment.progress.store', ['vertical' => $vertical->code()]) }}"
                      novalidate
                      data-progress-form>
                    @csrf

                    {{-- Đối tượng --}}
                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1.5" for="ts-target-id">
                            <span class="label-text font-medium">
                                Đối tượng <span class="text-error">*</span>
                            </span>
                        </label>
                        <select id="ts-target-id"
                                name="deployment_target_id"
                                class="select select-bordered select-sm w-full ts-init @error('deployment_target_id') select-error @enderror"
                                data-ts-placeholder="— Chọn đối tượng —"
                                data-target-nav
                                data-req="Vui lòng chọn đối tượng triển khai.">
                            <option value="">— Chọn đối tượng —</option>
                            @foreach($targets as $t)
                            <option value="{{ $t->id }}" @selected($currentTarget?->id == $t->id || old('deployment_target_id') == $t->id)>
                                {{ $t->targetOrganization?->name ?? 'Target #' . $t->id }}
                            </option>
                            @endforeach
                        </select>
                        @error('deployment_target_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-base-content/40">Chọn để lọc lịch sử bên phải</p>
                    </div>

                    {{-- Phase --}}
                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1.5" for="field-phase">
                            <span class="label-text font-medium">
                                Phase <span class="text-error">*</span>
                            </span>
                        </label>
                        <select id="field-phase"
                                name="phase"
                                class="select select-bordered select-sm w-full @error('phase') select-error @enderror"
                                data-req="Vui lòng chọn phase hiện tại.">
                            <option value="">— Chọn phase —</option>
                            @foreach($phases as $p)
                            <option value="{{ $p }}" @selected(old('phase', $currentTarget?->current_phase) === $p)>
                                {{ $phaseLabels[$p] ?? $p }}
                            </option>
                            @endforeach
                        </select>
                        @error('phase')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- % hoàn thành --}}
                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1.5" for="field-percent">
                            <span class="label-text font-medium">
                                % Hoàn thành <span class="text-error">*</span>
                            </span>
                            <span class="label-text-alt text-base-content/40 text-xs">0 – 100</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number"
                                   id="field-percent"
                                   name="percent"
                                   value="{{ old('percent', 0) }}"
                                   min="0" max="100"
                                   class="input input-bordered input-sm w-24 @error('percent') input-error @enderror"
                                   data-req="Vui lòng nhập % hoàn thành.">
                            <span class="text-sm text-base-content/40">%</span>
                        </div>
                        @error('percent')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Ghi chú --}}
                    <div class="form-control mb-5">
                        <label class="label py-0 pb-1.5" for="field-remark">
                            <span class="label-text font-medium">Ghi chú</span>
                            <span class="label-text-alt text-base-content/40 text-xs">Tùy chọn</span>
                        </label>
                        <textarea id="field-remark"
                                  name="remark"
                                  rows="3"
                                  class="textarea textarea-bordered textarea-sm w-full @error('remark') textarea-error @enderror"
                                  placeholder="Mô tả công việc đã thực hiện, vấn đề gặp phải...">{{ old('remark') }}</textarea>
                        @error('remark')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-full gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        Ghi nhận
                    </button>

                </form>
            </div>
        </div>
    </div>

    {{-- History list --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0">
            <div class="flex items-center justify-between px-4 py-3 border-b border-base-200">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h2 class="font-semibold text-sm">
                        Lịch sử
                        @if($currentTarget)
                        &mdash;
                        <span class="font-normal text-base-content/70">
                            {{ $currentTarget->targetOrganization?->name }}
                        </span>
                        @endif
                    </h2>
                </div>
                @if($logs->count())
                <span class="badge badge-ghost badge-sm">{{ $logs->count() }} mục</span>
                @endif
            </div>

            @forelse($logs as $log)
            <div class="px-4 py-3 border-b border-base-200 last:border-0 hover:bg-base-50 transition-colors">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        {{-- Phase + percent --}}
                        <div class="flex items-center gap-2 flex-wrap mb-1.5">
                            <span class="badge badge-outline badge-sm font-medium">{{ $phaseLabels[$log->phase] ?? $log->phase }}</span>
                            <span class="text-sm font-semibold text-base-content">{{ $log->percent }}%</span>
                            @if($log->checklistItem)
                            <span class="badge badge-ghost badge-sm">🗒 {{ $log->checklistItem->item_label }}</span>
                            @endif
                        </div>
                        {{-- Progress bar --}}
                        <div class="w-full bg-base-200 rounded-full h-1.5 mb-2">
                            <div class="h-1.5 rounded-full transition-all
                                {{ $log->percent >= 80 ? 'bg-success' : ($log->percent >= 40 ? 'bg-primary' : 'bg-warning') }}"
                                 style="width: {{ $log->percent }}%"></div>
                        </div>
                        @if($log->remark)
                        <p class="text-sm text-base-content/70 leading-snug">{{ $log->remark }}</p>
                        @endif
                    </div>
                    {{-- Meta --}}
                    <div class="shrink-0 text-right whitespace-nowrap">
                        <p class="text-xs font-medium text-base-content/60">{{ $log->loggedBy?->name }}</p>
                        <p class="text-xs text-base-content/40 mt-0.5">
                            {{ $log->logged_at?->format('d/m/Y') }}
                            <br>
                            <span class="text-[10px]">{{ $log->logged_at?->format('H:i') }}</span>
                        </p>
                    </div>
                </div>
            </div>
            @empty
            <div class="py-14 text-center text-base-content/40">
                <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm">Chưa có nhật ký nào.</p>
                <p class="text-xs mt-1">Chọn đối tượng và ghi nhận tiến độ đầu tiên.</p>
            </div>
            @endforelse
        </div>
    </div>

</div>

@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'Modules/Deployment/resources/assets/js/deployment.js',
    ], 'build/backend')
@endpush
