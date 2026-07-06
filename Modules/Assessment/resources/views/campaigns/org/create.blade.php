@extends('layouts.backend')
@section('title', 'Tạo Campaign mới')

@section('content')

<div class="flex items-center gap-2 text-sm text-base-content/50 mb-4">
    <a href="{{ route('campaigns.admin.index') }}" class="hover:text-primary">Campaigns</a>
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span>Tạo mới</span>
</div>

<form method="POST" action="{{ route('campaigns.admin.store') }}" x-data="campaignForm()">
@csrf

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

  {{-- Main form --}}
  <div class="lg:col-span-2 space-y-5">

    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h2 class="font-semibold mb-4">Thông tin cơ bản</h2>

            <div class="space-y-4">
                <div>
                    <label class="label label-text font-medium pb-1">Tiêu đề campaign <span class="text-error">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}"
                           class="input input-bordered w-full @error('title') input-error @enderror"
                           placeholder="VD: Tuyển Sales Manager — Đánh giá năng lực AI" required>
                    @error('title')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label label-text font-medium pb-1">Mô tả</label>
                    <textarea name="description" rows="3"
                              class="textarea textarea-bordered w-full @error('description') textarea-error @enderror"
                              placeholder="Giới thiệu về campaign, yêu cầu công việc...">{{ old('description') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label label-text font-medium pb-1">Vị trí tuyển dụng</label>
                        <input type="text" name="target_job_title_label" value="{{ old('target_job_title_label') }}"
                               class="input input-bordered w-full"
                               placeholder="VD: Sales Manager">
                    </div>
                    <div>
                        <label class="label label-text font-medium pb-1">Phòng ban</label>
                        <input type="text" name="target_department_label" value="{{ old('target_department_label') }}"
                               class="input input-bordered w-full"
                               placeholder="VD: Kinh doanh">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Domain requirements --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h2 class="font-semibold mb-4">Yêu cầu năng lực (Domain)</h2>
            <p class="text-sm text-base-content/50 mb-3">Chọn domain và điểm tối thiểu mà ứng viên cần đạt.</p>
            @php
              $domainNames = ['D1'=>'D1 — Digital Literacy','D2'=>'D2 — Data Literacy','D3'=>'D3 — AI Literacy','D4'=>'D4 — Workflow','D5'=>'D5 — Innovation','D6'=>'D6 — Performance'];
            @endphp
            <div class="space-y-2">
                @foreach($domainCodes as $code)
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="domain_codes[]" value="{{ $code }}"
                           id="domain_{{ $code }}"
                           class="checkbox checkbox-primary"
                           {{ in_array($code, old('domain_codes', [])) ? 'checked' : '' }}>
                    <label for="domain_{{ $code }}" class="text-sm w-40">{{ $domainNames[$code] }}</label>
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs text-base-content/50">min:</span>
                        <input type="number" name="domain_min_scores[{{ $code }}]"
                               value="{{ old('domain_min_scores.'.$code, 0) }}"
                               min="0" max="100" step="5"
                               class="input input-bordered input-xs w-20">
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Sandbox tasks --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h2 class="font-semibold mb-4">Task thực hành (Sandbox)</h2>
            <p class="text-sm text-base-content/50 mb-3">Chọn các task ứng viên cần hoàn thành.</p>

            @if($sandboxTasks->isEmpty())
            <p class="text-sm text-warning">Chưa có sandbox task nào. Tạo task tại <a href="{{ route('backend.sandbox-admin.index') }}" class="underline">Sandbox Admin</a>.</p>
            @else
            <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                @foreach($sandboxTasks as $task)
                <div class="flex items-center gap-3 p-2 bg-base-200/40 rounded-lg">
                    <input type="checkbox" name="sandbox_task_ids[]" value="{{ $task->id }}"
                           id="task_{{ $task->id }}"
                           class="checkbox checkbox-primary"
                           {{ in_array($task->id, old('sandbox_task_ids', [])) ? 'checked' : '' }}>
                    <label for="task_{{ $task->id }}" class="flex-1 cursor-pointer">
                        <p class="text-sm font-medium">{{ $task->title }}</p>
                        <p class="text-xs text-base-content/50">
                            {{ $task->environment?->name ?? '—' }}
                            @if($task->time_limit_minutes) · ⏱ {{ $task->time_limit_minutes }} phút @endif
                        </p>
                    </label>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

  </div>

  {{-- Sidebar: settings --}}
  <div class="space-y-4">

    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold mb-4">Cài đặt tham gia</h3>

            <div class="space-y-4">
                <div>
                    <label class="label label-text text-sm font-medium pb-1">Trust Level tối thiểu <span class="text-error">*</span></label>
                    <select name="min_trust_level" class="select select-bordered w-full">
                        @foreach([0=>'Lv0 — Tất cả',1=>'Lv1 — Email (khuyến nghị)',2=>'Lv2 — Điện thoại'] as $lv => $label)
                        <option value="{{ $lv }}" {{ old('min_trust_level', 1) == $lv ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label label-text text-sm font-medium pb-1">TDWCF tối thiểu</label>
                    <input type="number" name="min_tdwcf_score" value="{{ old('min_tdwcf_score') }}"
                           min="0" max="100" step="5"
                           class="input input-bordered w-full" placeholder="Để trống = không giới hạn">
                </div>

                <div>
                    <label class="label label-text text-sm font-medium pb-1">Số lượng tối đa</label>
                    <input type="number" name="max_participants" value="{{ old('max_participants') }}"
                           min="1" class="input input-bordered w-full" placeholder="Để trống = không giới hạn">
                </div>

                <div>
                    <label class="label label-text text-sm font-medium pb-1">Trạng thái ban đầu <span class="text-error">*</span></label>
                    <select name="status" class="select select-bordered w-full">
                        <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Nháp</option>
                        <option value="open" {{ old('status') === 'open' ? 'selected' : '' }}>Mở ngay</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="label label-text text-sm pb-1">Mở từ</label>
                        <input type="datetime-local" name="open_from" value="{{ old('open_from') }}"
                               class="input input-bordered input-sm w-full">
                    </div>
                    <div>
                        <label class="label label-text text-sm pb-1">Hạn nộp</label>
                        <input type="datetime-local" name="open_until" value="{{ old('open_until') }}"
                               class="input input-bordered input-sm w-full">
                    </div>
                </div>

                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-3">
                        <input type="checkbox" name="is_anonymous_to_org" value="1"
                               class="checkbox checkbox-primary"
                               {{ old('is_anonymous_to_org', true) ? 'checked' : '' }}>
                        <div>
                            <span class="label-text font-medium">Ẩn danh với org</span>
                            <p class="text-xs text-base-content/50">Org chỉ thấy "Ứng viên #1, #2..." cho đến khi invite</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="flex gap-2">
        <a href="{{ route('campaigns.admin.index') }}" class="btn btn-ghost flex-1">Huỷ</a>
        <button type="submit" class="btn btn-primary flex-1">Tạo Campaign</button>
    </div>

  </div>

</div>
</form>

@push('scripts')
<script>
function campaignForm() {
    return {};
}
</script>
@endpush

@endsection
