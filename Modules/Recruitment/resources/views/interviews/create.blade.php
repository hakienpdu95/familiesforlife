@extends('layouts.backend')

@section('title', 'Lên lịch phỏng vấn')


@section('content')
<div
    x-data="{
        panelists: [{ user_id: '', role: 'interviewer' }],
        addPanelist() { this.panelists.push({ user_id: '', role: 'interviewer' }) },
        removePanelist(i) { this.panelists.splice(i, 1) },
    }"
    class="p-6 max-w-2xl"
>
    <div class="mb-5">
        <h1 class="text-xl font-bold">Lên lịch phỏng vấn</h1>
        <p class="text-sm opacity-60 mt-0.5">Ứng viên: <span class="font-medium text-base-content">{{ $application->candidate?->full_name }}</span></p>
    </div>

    <form method="POST"
          action="{{ route('backend.recruitment.applications.interviews.store', $application) }}"
          class="space-y-5"
          data-interview-form>
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

        {{-- Lịch & loại --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 space-y-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-base-content/50">Thông tin lịch</p>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label" for="ts-interview-type">
                            <span class="label-text font-medium">Loại phỏng vấn <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-interview-type" name="interview_type" class="select select-bordered select-sm ts-init" required>
                            @foreach($interviewTypes as $type)
                            <option value="{{ $type['value'] }}" {{ old('interview_type', 'video') === $type['value'] ? 'selected' : '' }}>
                                {{ $type['text'] }}
                            </option>
                            @endforeach
                        </select>
                        @error('interview_type')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label" for="ts-stage-id">
                            <span class="label-text font-medium">Stage liên quan <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-stage-id" name="stage_id" class="select select-bordered select-sm ts-init" required>
                            @foreach($stages as $stage)
                            <option value="{{ $stage->id }}" {{ $application->current_stage_id == $stage->id ? 'selected' : '' }}>
                                {{ $stage->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('stage_id')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-control">
                    <label class="label" for="title">
                        <span class="label-text font-medium">Tiêu đề</span>
                    </label>
                    <input id="title" type="text" name="title"
                           value="{{ old('title') }}"
                           class="input input-bordered input-sm"
                           placeholder="VD: Phỏng vấn kỹ thuật vòng 1"
                           maxlength="200">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label" for="fp-scheduled-at">
                            <span class="label-text font-medium">Thời gian bắt đầu <span class="text-error">*</span></span>
                        </label>
                        <input id="fp-scheduled-at" name="scheduled_at"
                               value="{{ old('scheduled_at') }}"
                               class="input input-bordered input-sm fp-init"
                               data-fp-mode="datetime"
                               placeholder="dd/mm/yyyy HH:MM"
                               autocomplete="off">
                        @error('scheduled_at')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label" for="duration_minutes">
                            <span class="label-text font-medium">Thời lượng (phút) <span class="text-error">*</span></span>
                        </label>
                        <input id="duration_minutes" type="number" name="duration_minutes"
                               value="{{ old('duration_minutes', 60) }}"
                               class="input input-bordered input-sm @error('duration_minutes') input-error @enderror"
                               min="15" max="480" required>
                        @error('duration_minutes')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-control">
                    <label class="label" for="location">
                        <span class="label-text font-medium">Địa điểm</span>
                    </label>
                    <input id="location" type="text" name="location"
                           value="{{ old('location') }}"
                           class="input input-bordered input-sm"
                           placeholder="Phòng họp A / Online" maxlength="300">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label" for="meeting_url">
                            <span class="label-text font-medium">Meeting URL</span>
                        </label>
                        <input id="meeting_url" type="url" name="meeting_url"
                               value="{{ old('meeting_url') }}"
                               class="input input-bordered input-sm"
                               placeholder="https://meet.google.com/...">
                    </div>
                    <div class="form-control">
                        <label class="label" for="meeting_id">
                            <span class="label-text font-medium">Meeting ID</span>
                        </label>
                        <input id="meeting_id" type="text" name="meeting_id"
                               value="{{ old('meeting_id') }}"
                               class="input input-bordered input-sm"
                               maxlength="100">
                    </div>
                </div>

                <div class="form-control">
                    <label class="label" for="interviewer_note">
                        <span class="label-text font-medium">Ghi chú nội bộ</span>
                    </label>
                    <textarea id="interviewer_note" name="interviewer_note"
                              class="textarea textarea-bordered textarea-sm"
                              rows="2"
                              placeholder="Lưu ý cho panel...">{{ old('interviewer_note') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Panel Assignment --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-base-content/50">Panel phỏng vấn</p>
                    <button type="button" @click="addPanelist()"
                            class="btn btn-ghost btn-xs">
                        + Thêm người
                    </button>
                </div>

                <template x-for="(panelist, idx) in panelists" :key="idx">
                    <div class="flex gap-2 items-center">
                        <select :name="'panelists[' + idx + '][user_id]'"
                                class="select select-bordered select-sm flex-1"
                                x-model="panelist.user_id">
                            <option value="">— Chọn người —</option>
                            @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                        <select :name="'panelists[' + idx + '][role]'"
                                class="select select-bordered select-sm w-44"
                                x-model="panelist.role">
                            @foreach($panelistRoles as $role)
                            <option value="{{ $role['value'] }}">{{ $role['text'] }}</option>
                            @endforeach
                        </select>
                        <button type="button" @click="removePanelist(idx)"
                                class="btn btn-ghost btn-xs text-error shrink-0">✕</button>
                    </div>
                </template>

                <p x-show="panelists.length === 0" class="text-xs text-base-content/40">
                    Chưa có panelist nào. Nhấn "+ Thêm người" để thêm.
                </p>
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('backend.recruitment.applications.show', $application) }}"
               class="btn btn-ghost btn-sm">Hủy</a>
            <button type="submit" class="btn btn-primary btn-sm">Tạo lịch phỏng vấn</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
@vite([
    'resources/js/modules/toastify.js',
    'resources/js/modules/flatpickr.js',
    'resources/js/modules/tom-select.js',
    'Modules/Recruitment/resources/assets/sass/recruitment.scss',
    'Modules/Recruitment/resources/assets/js/recruitment.js',
], 'build/backend')
@endpush
