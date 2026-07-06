@extends('layouts.backend')

@section('title', 'Tạo Offer Letter')


@section('content')
<div class="p-6 max-w-2xl">

    <div class="mb-5">
        <h1 class="text-xl font-bold">Tạo Offer Letter</h1>
        <p class="text-sm opacity-60 mt-0.5">Ứng viên: <span class="font-medium text-base-content">{{ $application->candidate?->full_name }}</span></p>
    </div>

    <form method="POST"
          action="{{ route('backend.recruitment.applications.offers.store', $application) }}"
          class="space-y-5">
        @csrf

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

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label" for="salary_offered">
                            <span class="label-text font-medium">Mức lương đề xuất <span class="text-error">*</span></span>
                        </label>
                        <input id="salary_offered" type="number" name="salary_offered"
                               value="{{ old('salary_offered') }}"
                               class="input input-bordered input-sm @error('salary_offered') input-error @enderror"
                               min="0" step="100000" required placeholder="0">
                        @error('salary_offered')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label" for="ts-currency">
                            <span class="label-text font-medium">Đơn vị tiền tệ</span>
                        </label>
                        <select id="ts-currency" name="currency" class="select select-bordered select-sm ts-init">
                            <option value="VND" {{ old('currency', 'VND') === 'VND' ? 'selected' : '' }}>VND</option>
                            <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label" for="fp-start-date">
                            <span class="label-text font-medium">Ngày bắt đầu <span class="text-error">*</span></span>
                        </label>
                        <input id="fp-start-date" name="start_date"
                               value="{{ old('start_date') }}"
                               class="input input-bordered input-sm fp-init"
                               placeholder="dd/mm/yyyy" autocomplete="off">
                        @error('start_date')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label" for="probation_days">
                            <span class="label-text font-medium">Thử việc (ngày) <span class="text-error">*</span></span>
                        </label>
                        <input id="probation_days" type="number" name="probation_days"
                               value="{{ old('probation_days', 60) }}"
                               class="input input-bordered input-sm @error('probation_days') input-error @enderror"
                               min="0" max="365" required>
                        @error('probation_days')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-control">
                    <label class="label" for="fp-expire-at">
                        <span class="label-text font-medium">Hạn trả lời</span>
                    </label>
                    <input id="fp-expire-at" name="expire_at"
                           value="{{ old('expire_at') }}"
                           class="input input-bordered input-sm fp-init"
                           placeholder="dd/mm/yyyy" autocomplete="off">
                    <div class="label">
                        <span class="label-text-alt opacity-50">Để trống nếu không giới hạn thời gian</span>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label" for="benefits_note">
                        <span class="label-text font-medium">Ghi chú phúc lợi</span>
                    </label>
                    <textarea id="benefits_note" name="benefits_note"
                              class="jodit-editor textarea textarea-bordered textarea-sm w-full"
                              data-jodit-preset="compact"
                              rows="4"
                              placeholder="Thưởng KPI, bảo hiểm sức khỏe, 13 tháng lương...">{{ old('benefits_note') }}</textarea>
                </div>

            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('backend.recruitment.applications.show', $application) }}"
               class="btn btn-ghost btn-sm">Hủy</a>
            <button type="submit" class="btn btn-primary btn-sm">Tạo offer</button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
@vite([
    'resources/js/modules/toastify.js',
    'resources/js/modules/flatpickr.js',
    'resources/js/modules/tom-select.js',
    'resources/js/modules/jodit.js',
    'Modules/Recruitment/resources/assets/sass/recruitment.scss',
    'Modules/Recruitment/resources/assets/js/recruitment.js',
], 'build/backend')
@endpush
