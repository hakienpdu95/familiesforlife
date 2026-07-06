@extends('layouts.backend')

@section('title', 'Pipeline Stages — Recruitment')


@section('content')
<div class="p-6 space-y-5 max-w-4xl">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">Cấu hình Pipeline Stages</h1>
            <p class="text-sm opacity-60 mt-0.5">Thiết kế quy trình tuyển dụng theo thứ tự xử lý ứng viên</p>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    {{-- Current stages --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4">
            <h2 class="font-semibold mb-3">Stages hiện tại ({{ $stages->count() }})</h2>

            <div class="space-y-2">
                @forelse($stages as $stage)
                <div class="flex items-center gap-3 p-3 border border-base-200 rounded-lg {{ $stage->is_active ? '' : 'opacity-50' }}">
                    @if($stage->color_hex)
                    <span class="w-3 h-3 rounded-full shrink-0" style="background: {{ $stage->color_hex }}"></span>
                    @else
                    <span class="w-3 h-3 rounded-full bg-base-300 shrink-0"></span>
                    @endif

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-sm">{{ $stage->name }}</span>
                            <span class="badge badge-outline badge-xs">{{ $stage->stage_type?->label() }}</span>
                            @if($stage->require_score)
                            <span class="badge badge-warning badge-xs">Cần điểm</span>
                            @endif
                            @if(!$stage->is_active)
                            <span class="badge badge-ghost badge-xs">Ẩn</span>
                            @endif
                        </div>
                        <p class="text-xs opacity-40">Thứ tự: {{ $stage->sort_order }}</p>
                    </div>

                    <form method="POST" action="{{ route('backend.recruitment.pipeline-stages.update', $stage) }}"
                          class="flex items-center gap-2">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="name" value="{{ $stage->name }}">
                        <input type="hidden" name="is_active" value="{{ $stage->is_active ? '0' : '1' }}">
                        <input type="hidden" name="require_score" value="{{ $stage->require_score ? '1' : '0' }}">
                        <input type="hidden" name="send_notification" value="{{ $stage->send_notification ? '1' : '0' }}">
                        <button type="submit" class="btn btn-ghost btn-xs">
                            {{ $stage->is_active ? 'Ẩn' : 'Hiện' }}
                        </button>
                    </form>
                </div>
                @empty
                <p class="text-center py-6 text-sm opacity-50">Chưa có stage nào</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Add new stage --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4">
            <h2 class="font-semibold mb-3">Thêm stage mới</h2>

            <form method="POST" action="{{ route('backend.recruitment.pipeline-stages.store') }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-medium">Tên stage <span class="text-error">*</span></span></label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="input input-bordered input-sm @error('name') input-error @enderror"
                               placeholder="VD: Phỏng vấn CEO" required>
                        @error('name')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-medium">Loại stage <span class="text-error">*</span></span></label>
                        <select name="stage_type" class="select select-bordered select-sm" required>
                            @foreach($stageTypes as $type)
                            <option value="{{ $type['value'] }}" {{ old('stage_type') === $type['value'] ? 'selected' : '' }}>
                                {{ $type['text'] }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-medium">Màu hiển thị</span></label>
                        <input type="color" name="color_hex" value="{{ old('color_hex', '#6366f1') }}"
                               class="input input-bordered input-sm h-9 cursor-pointer">
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <label class="cursor-pointer flex items-center gap-2">
                        <input type="checkbox" name="require_score" value="1" class="checkbox checkbox-sm"
                               {{ old('require_score') ? 'checked' : '' }}>
                        <span class="text-sm">Yêu cầu đánh giá trước khi pass</span>
                    </label>
                    <label class="cursor-pointer flex items-center gap-2">
                        <input type="checkbox" name="send_notification" value="1" class="checkbox checkbox-sm"
                               {{ old('send_notification', true) ? 'checked' : '' }}>
                        <span class="text-sm">Gửi thông báo email cho ứng viên</span>
                    </label>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary btn-sm">Thêm stage</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
