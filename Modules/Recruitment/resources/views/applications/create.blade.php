@extends('layouts.backend')

@section('title', 'Tạo đơn ứng tuyển')


@section('content')
<div class="p-6 max-w-2xl">

    <div class="mb-5">
        <h1 class="text-xl font-bold">Tạo đơn ứng tuyển</h1>
        <p class="text-sm opacity-60 mt-0.5">Ứng viên: <span class="font-medium text-base-content">{{ $candidate->full_name }}</span> · {{ $candidate->email }}</p>
    </div>

    <form method="POST" action="{{ route('backend.recruitment.applications.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="candidate_id" value="{{ $candidate->id }}">

        @if($errors->any())
        <div class="alert alert-error">
            <ul class="list-disc pl-4 text-sm space-y-0.5">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 space-y-4">

                <div class="form-control">
                    <label class="label" for="ts-stage-id">
                        <span class="label-text font-medium">Stage bắt đầu <span class="text-error">*</span></span>
                    </label>
                    <select id="ts-stage-id" name="stage_id" class="select select-bordered select-sm ts-init" required>
                        @foreach($stages as $stage)
                        @if(!in_array($stage->stage_type->value, ['hired', 'rejected']))
                        <option value="{{ $stage->id }}" {{ old('stage_id') == $stage->id ? 'selected' : '' }}>
                            {{ $stage->name }}
                        </option>
                        @endif
                        @endforeach
                    </select>
                    @error('stage_id')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label" for="jp_job_post_id">
                        <span class="label-text font-medium">Tin tuyển dụng (UUID)</span>
                    </label>
                    <input id="jp_job_post_id" type="text" name="jp_job_post_id"
                           value="{{ old('jp_job_post_id', request('jp_job_post_id')) }}"
                           class="input input-bordered input-sm font-mono @error('jp_job_post_id') input-error @enderror"
                           placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                    <div class="label">
                        <span class="label-text-alt opacity-50">UUID từ Job Posting Center (tùy chọn, khuyến nghị)</span>
                    </div>
                    @error('jp_job_post_id')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label" for="ts-apply-source">
                        <span class="label-text font-medium">Nguồn ứng tuyển <span class="text-error">*</span></span>
                    </label>
                    <select id="ts-apply-source" name="apply_source" class="select select-bordered select-sm ts-init" required>
                        @foreach($sources as $src)
                        <option value="{{ $src['value'] }}" {{ old('apply_source', 'direct') === $src['value'] ? 'selected' : '' }}>
                            {{ $src['text'] }}
                        </option>
                        @endforeach
                    </select>
                    @error('apply_source')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label" for="expected_salary">
                            <span class="label-text font-medium">Lương kỳ vọng (VNĐ)</span>
                        </label>
                        <input id="expected_salary" type="number" name="expected_salary"
                               value="{{ old('expected_salary') }}"
                               class="input input-bordered input-sm"
                               min="0" step="500000" placeholder="0">
                    </div>
                    <div class="form-control">
                        <label class="label" for="notice_period_days">
                            <span class="label-text font-medium">Thời gian thông báo (ngày)</span>
                        </label>
                        <input id="notice_period_days" type="number" name="notice_period_days"
                               value="{{ old('notice_period_days') }}"
                               class="input input-bordered input-sm"
                               min="0" placeholder="30">
                    </div>
                </div>

                <div class="form-control">
                    <label class="label" for="cover_letter">
                        <span class="label-text font-medium">Thư xin việc</span>
                    </label>
                    <textarea id="cover_letter" name="cover_letter"
                              class="jodit-editor textarea textarea-bordered textarea-sm w-full"
                              data-jodit-preset="compact"
                              rows="5"
                              placeholder="Nội dung thư xin việc...">{{ old('cover_letter') }}</textarea>
                </div>

            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('backend.recruitment.candidates.show', $candidate) }}" class="btn btn-ghost btn-sm">Hủy</a>
            <button type="submit" class="btn btn-primary btn-sm">Tạo đơn ứng tuyển</button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
@vite([
    'resources/js/modules/toastify.js',
    'resources/js/modules/tom-select.js',
    'resources/js/modules/jodit.js',
    'Modules/Recruitment/resources/assets/sass/recruitment.scss',
    'Modules/Recruitment/resources/assets/js/recruitment.js',
], 'build/backend')
@endpush
