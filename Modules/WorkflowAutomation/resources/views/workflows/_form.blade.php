@php
$isEdit = $workflow !== null;

// Build init step data — read action_config first, fallback to flat columns
$initSteps = $isEdit ? $workflow->steps->map(function($s) {
    $ac = is_array($s->action_config) ? $s->action_config : json_decode($s->action_config ?? '{}', true) ?? [];
    $cc = is_array($s->condition_config) ? $s->condition_config : json_decode($s->condition_config ?? 'null', true);

    // Merge flat columns into action_config for legacy steps (action_config may be null on old records)
    foreach ([
        'email_to', 'email_subject', 'email_template',
        'notif_title', 'notif_body', 'notif_target',
        'update_model', 'update_field', 'update_value',
        'webhook_url', 'webhook_method', 'webhook_secret',
    ] as $flat) {
        if (!array_key_exists($flat, $ac) && !empty($s->{$flat})) {
            $ac[$flat] = $s->{$flat};
        }
    }

    return [
        'action_type'      => $s->action_type,
        'step_type'        => $s->step_type ?? 1,
        'step_label'       => $s->label ?? '',
        'delay_minutes'    => $s->delay_minutes ?? 0,
        'halt_on_fail'     => (bool) ($s->halt_on_fail ?? false),
        'step_output_key'  => $s->step_output_key ?? '',
        'action_config'    => $ac,
        'condition_config' => $cc ?? ['match' => 'ALL', 'conditions' => []],
        'headers'          => $s->headers->map(fn($h) => [
            'header_key'   => $h->header_key,
            'header_value' => $h->header_value,
        ])->toArray(),
        '_show_advanced'   => !empty($s->halt_on_fail) || !empty($s->step_output_key) || !empty($cc['conditions']),
        '_show_cond'       => !empty($cc['conditions']),
    ];
})->toArray() : [];

$initData = $isEdit ? [
    'name'           => $workflow->name,
    'description'    => $workflow->description,
    'trigger_type'   => $workflow->trigger_type,
    'trigger_params' => $workflow->triggerParams->map(fn($p) => [
        'param_key'   => $p->param_key,
        'param_value' => $p->param_value,
        'param_type'  => $p->param_type,
    ])->toArray(),
    'condition_match'=> $workflow->condition_match,
    'cooldown_type'  => $workflow->cooldown_type,
    'is_active'      => $workflow->is_active,
    'priority'       => $workflow->priority,
    'conditions'     => $workflow->conditions->map(fn($c) => [
        'field'      => $c->field,
        'operator'   => $c->operator,
        'value'      => $c->value,
        'value_type' => $c->value_type,
    ])->toArray(),
    'steps' => $initSteps,
] : [
    'name' => '', 'description' => '', 'trigger_type' => '', 'trigger_params' => [],
    'condition_match' => 3, 'cooldown_type' => 0, 'is_active' => false, 'priority' => 5,
    'conditions' => [], 'steps' => [],
];

$stepFields = [
    1 => ['name', 'description', 'priority', 'is_active'],
    2 => ['trigger_type', 'trigger_params'],
    3 => ['condition_match', 'conditions'],
    4 => ['steps'],
    5 => ['cooldown_type'],
];
$initialStep = 1;
foreach ($stepFields as $step => $fields) {
    foreach ($fields as $field) {
        if ($errors->has($field) || collect($errors->keys())->contains(fn($k) => str_starts_with($k, $field . '.'))) {
            $initialStep = $step;
            break 2;
        }
    }
}

$sd = [
    'metaUrl'     => route('backend.api.workflows.meta'),
    'initData'    => $initData,
    'initialStep' => $initialStep,
];

$priorityOptions = [
    1  => ['label' => 'Rất cao',  'desc' => 'Chạy trước tất cả'],
    3  => ['label' => 'Cao',      'desc' => 'Ưu tiên cao'],
    5  => ['label' => 'Bình thường', 'desc' => 'Mặc định'],
    7  => ['label' => 'Thấp',    'desc' => 'Chạy sau'],
    10 => ['label' => 'Rất thấp','desc' => 'Chạy cuối cùng'],
];
@endphp

<div x-data="wfBuilderPage({{ Js::from($sd) }})">

{{-- ── Page header ─────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">
            {{ $isEdit ? 'Sửa workflow' : 'Tạo Workflow mới' }}
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            {{ $isEdit ? 'Cập nhật cấu hình, trigger và hành động' : 'Thiết lập trigger, điều kiện và hành động tự động' }}
        </p>
    </div>
    <a href="{{ $isEdit ? route('workflows.show', $workflow) : route('workflows.index') }}"
       class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

{{-- ── Error banner ────────────────────────────────────────────────────── --}}
@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<div x-show="metaError" class="alert alert-warning py-3 px-4 mb-5 text-sm" x-cloak>
    Không thể tải cấu hình workflow. Vui lòng tải lại trang.
</div>

<form id="wf-form" method="POST" action="{{ $formAction }}" novalidate data-wf-form>
    @csrf
    @if($method !== 'POST')<input type="hidden" name="_method" value="{{ $method }}">@endif

    {{-- ── Wizard step indicator — clickable for visited steps ──────────── --}}
    <div class="flex items-center gap-0 mb-6">
        <template x-for="(label, idx) in steps" :key="idx">
            <div class="flex items-center flex-1 last:flex-none">
                <div class="flex flex-col items-center gap-1 min-w-0">
                    <button type="button"
                            :disabled="currentStep < idx + 1"
                            @click="currentStep > idx + 1 && goToStep(idx + 1)"
                            :class="[stepDotClass(idx), currentStep > idx + 1 ? 'cursor-pointer hover:ring-2 hover:ring-primary/40' : '']"
                            class="wizard-step-dot transition-all">
                        <template x-if="currentStep > idx + 1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </template>
                        <template x-if="currentStep <= idx + 1">
                            <span x-text="idx + 1"></span>
                        </template>
                    </button>
                    <span class="text-xs whitespace-nowrap hidden sm:block"
                          :class="currentStep === idx + 1 ? 'text-primary font-semibold' : (currentStep > idx + 1 ? 'text-success text-opacity-80' : 'text-base-content/40')"
                          x-text="label"></span>
                </div>
                <template x-if="idx < steps.length - 1">
                    <div class="wizard-step-line" :class="stepLineClass(idx)"></div>
                </template>
            </div>
        </template>
    </div>

    {{-- ── Wizard content card ──────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">

        {{-- ══════════════════════════════════════════════════════════════════
             BƯỚC 1 — Thông tin cơ bản
        ════════════════════════════════════════════════════════════════════ --}}
        <div x-show="currentStep === 1" class="p-6 space-y-4">

            <h2 class="card-title text-base mb-1">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Thông tin cơ bản
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- Tên workflow --}}
                <div class="form-control sm:col-span-2">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Tên workflow <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="name" x-model="form.name"
                           class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                           placeholder="VD: Gửi email khi có lead mới, Offboarding nhân viên..."
                           autofocus>
                    @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                {{-- Mô tả --}}
                <div class="form-control sm:col-span-2">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Mô tả</span>
                        <span class="label-text-alt text-base-content/40 text-xs">Tuỳ chọn</span>
                    </label>
                    <textarea name="description" x-model="form.description" rows="2"
                              class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                              placeholder="Mô tả mục đích workflow, ai được kích hoạt, khi nào chạy..."></textarea>
                    @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                {{-- Mức ưu tiên — select thay vì number input --}}
                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Mức ưu tiên</span>
                        <span class="label-text-alt text-xs text-base-content/40"
                              x-text="priorityLabel(form.priority)"></span>
                    </label>
                    <div class="flex gap-1.5">
                        @foreach($priorityOptions as $val => $opt)
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="priority" value="{{ $val }}"
                                   x-model.number="form.priority"
                                   class="sr-only peer">
                            <div class="text-center py-1.5 rounded-lg border border-base-200 text-xs font-medium
                                        peer-checked:bg-primary peer-checked:text-primary-content peer-checked:border-primary
                                        hover:border-primary/50 transition-all cursor-pointer select-none"
                                 title="{{ $opt['desc'] }}">
                                {{ $opt['label'] }}
                            </div>
                        </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-base-content/40">Workflow ưu tiên "Rất cao" chạy trước khi có nhiều workflow cùng trigger.</p>
                </div>

                {{-- Trạng thái --}}
                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Trạng thái sau khi tạo</span>
                    </label>
                    <input type="hidden" name="is_active" :value="form.is_active ? '1' : '0'">
                    <div class="flex flex-col gap-2 mt-1">
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group p-2.5 rounded-xl border"
                               :class="form.is_active ? 'border-success bg-success/5' : 'border-base-200'">
                            <input type="checkbox" x-model="form.is_active"
                                   class="checkbox checkbox-sm checkbox-success mt-0.5 shrink-0">
                            <div>
                                <span class="text-sm font-medium" :class="form.is_active ? 'text-success' : ''">
                                    <span x-text="form.is_active ? '✓ Kích hoạt ngay' : 'Tắt (thủ công kích hoạt sau)'"></span>
                                </span>
                                <p class="text-xs text-base-content/50 mt-0.5"
                                   x-text="form.is_active ? 'Workflow bắt đầu chạy ngay sau khi lưu.' : 'Workflow sẽ không chạy cho đến khi bạn bật.'"></p>
                            </div>
                        </label>
                    </div>
                </div>

            </div>

            <div class="flex justify-end pt-2">
                <button type="button" @click="nextStep()" class="btn btn-primary btn-sm gap-1.5">
                    Tiếp theo: Trigger
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════
             BƯỚC 2 — Trigger
        ════════════════════════════════════════════════════════════════════ --}}
        <div x-show="currentStep === 2" class="p-6 space-y-4">

            <h2 class="card-title text-base mb-1">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Sự kiện kích hoạt (Trigger)
            </h2>
            <p class="text-xs text-base-content/50 -mt-2">Chọn sự kiện xảy ra trong hệ thống để workflow bắt đầu chạy.</p>

            <div class="form-control">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Loại trigger <span class="text-error">*</span></span>
                </label>
                <div x-show="metaLoading" class="skeleton h-9 w-full rounded-lg"></div>
                <select x-show="!metaLoading" name="trigger_type" x-model="form.trigger_type"
                        @change="onTriggerChange()"
                        class="select select-bordered select-sm w-full @error('trigger_type') select-error @enderror">
                    <option value="">— Chọn trigger —</option>
                    <template x-for="[mod, triggers] in Object.entries(meta.trigger_groups ?? {})" :key="mod">
                        <optgroup :label="mod">
                            <template x-for="t in triggers" :key="t.type">
                                <option :value="t.type" x-text="t.label"></option>
                            </template>
                        </optgroup>
                    </template>
                </select>
                @error('trigger_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            {{-- Trigger context info card --}}
            <template x-if="currentTrigger">
                <div class="rounded-xl bg-primary/5 border border-primary/15 px-4 py-3 text-xs space-y-1.5">
                    <div class="flex items-center gap-1.5 font-semibold text-primary">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span x-text="currentTrigger.label"></span>
                    </div>
                    <p class="text-base-content/60">
                        Module: <span class="font-mono font-medium text-base-content/80" x-text="currentTrigger.module"></span>
                    </p>
                    <template x-if="currentTrigger.available_fields?.length > 0">
                        <p class="text-base-content/60">
                            Dữ liệu khả dụng trong điều kiện:
                            <span class="text-base-content/80"
                                  x-text="currentTrigger.available_fields.map(f => f.label).join(' · ')"></span>
                        </p>
                    </template>
                </div>
            </template>

            {{-- Trigger config fields --}}
            <template x-if="currentTrigger?.config_fields?.length > 0">
                <div class="space-y-3 p-3 border border-base-200 rounded-xl bg-base-50">
                    <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">Cấu hình chi tiết trigger</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <template x-for="field in currentTrigger.config_fields" :key="field.key">
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium text-sm" x-text="field.label"></span>
                                    <span x-show="!field.required" class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                                </label>
                                <input :type="field.type === 'number' ? 'number' : 'text'"
                                       x-model="triggerConfig[field.key]"
                                       @change="syncTriggerParams()"
                                       :placeholder="field.hint ?? ''"
                                       class="input input-bordered input-sm w-full">
                                <p x-show="field.hint" class="mt-1 text-xs text-base-content/40" x-text="field.hint"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Hidden trigger_params --}}
            <template x-for="(p, i) in form.trigger_params" :key="i">
                <div class="hidden">
                    <input type="hidden" :name="'trigger_params[' + i + '][param_key]'"   :value="p.param_key">
                    <input type="hidden" :name="'trigger_params[' + i + '][param_value]'" :value="p.param_value">
                    <input type="hidden" :name="'trigger_params[' + i + '][param_type]'"  :value="p.param_type">
                </div>
            </template>

            <div class="flex items-center justify-between pt-2">
                <button type="button" @click="prevStep()" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Cơ bản
                </button>
                <button type="button" @click="nextStep()" class="btn btn-primary btn-sm gap-1.5">
                    Tiếp theo: Điều kiện
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════
             BƯỚC 3 — Điều kiện
        ════════════════════════════════════════════════════════════════════ --}}
        <div x-show="currentStep === 3" class="p-6 space-y-4">

            <div class="flex items-center justify-between mb-1">
                <div>
                    <h2 class="card-title text-base">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                        </svg>
                        Điều kiện lọc
                    </h2>
                    <p class="text-xs text-base-content/50 mt-0.5">Workflow chỉ chạy khi thỏa điều kiện. Bỏ trống = chạy với mọi trigger.</p>
                </div>
                <button type="button" @click="addCondition()" :disabled="!form.trigger_type"
                        class="btn btn-outline btn-xs gap-1" :title="!form.trigger_type ? 'Chọn trigger trước' : ''">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Thêm điều kiện
                </button>
            </div>

            {{-- Khớp điều kiện --}}
            <div class="form-control max-w-xs" x-show="form.conditions.length > 0">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium text-sm">Chế độ khớp</span>
                </label>
                <select name="condition_match" x-model.number="form.condition_match"
                        class="select select-bordered select-sm w-full">
                    <option value="3">Không cần điều kiện</option>
                    <option value="1">TẤT CẢ đúng (AND)</option>
                    <option value="2">ÍT NHẤT 1 đúng (OR)</option>
                </select>
            </div>

            {{-- Empty state --}}
            <template x-if="form.conditions.length === 0">
                <div class="empty-state py-8 border-2 border-dashed border-base-200 rounded-xl">
                    <svg class="empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                    </svg>
                    <p class="empty-title">Không có điều kiện</p>
                    <p class="empty-desc">Workflow sẽ chạy với mọi trigger phù hợp.</p>
                    <button type="button" @click="addCondition()" :disabled="!form.trigger_type"
                            class="btn btn-outline btn-xs mt-2">
                        Thêm điều kiện đầu tiên
                    </button>
                </div>
            </template>

            {{-- Condition rows --}}
            <div class="space-y-2">
                <template x-for="(cond, ci) in form.conditions" :key="ci">
                    <div class="p-3 bg-base-200/50 rounded-xl space-y-2">
                        <div class="flex flex-wrap gap-2 items-end">

                            {{-- Field --}}
                            <div class="form-control flex-1 min-w-40">
                                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Field</span></label>
                                <select :name="'conditions[' + ci + '][field]'" x-model="cond.field"
                                        class="select select-bordered select-xs w-full">
                                    <option value="">— Chọn field —</option>
                                    <template x-for="f in currentTrigger?.available_fields ?? []" :key="f.key">
                                        <option :value="f.key" x-text="f.label + ' (' + f.key + ')'"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- Operator --}}
                            <div class="form-control w-40">
                                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">So sánh</span></label>
                                <select :name="'conditions[' + ci + '][operator]'" x-model="cond.operator"
                                        class="select select-bordered select-xs w-full">
                                    <template x-for="op in meta.operators ?? []" :key="op.value">
                                        <option :value="op.value" x-text="op.label"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- Value --}}
                            <div class="form-control flex-1 min-w-28" x-show="!['is_empty','is_not_empty'].includes(cond.operator)">
                                <label class="label py-0 pb-1">
                                    <span class="label-text text-xs font-medium">Giá trị</span>
                                    <span x-show="cond.operator === 'in' || cond.operator === 'not_in'"
                                          class="label-text-alt text-xs text-base-content/40">Dùng | phân cách</span>
                                </label>
                                <input :name="'conditions[' + ci + '][value]'" x-model="cond.value"
                                       :placeholder="cond.operator === 'in' ? 'A|B|C' : 'Giá trị...'"
                                       class="input input-bordered input-xs w-full">
                            </div>

                            {{-- Value type --}}
                            <div class="form-control w-24">
                                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Kiểu</span></label>
                                <select :name="'conditions[' + ci + '][value_type]'" x-model.number="cond.value_type"
                                        class="select select-bordered select-xs w-full"
                                        title="Kiểu dữ liệu để so sánh đúng">
                                    <option value="1">Text</option>
                                    <option value="2">Số</option>
                                    <option value="3">Số thực</option>
                                    <option value="4">Bool</option>
                                </select>
                            </div>

                            <button type="button" @click="removeCondition(ci)"
                                    class="btn btn-ghost btn-xs btn-square text-error/40 hover:text-error mb-0.5" title="Xoá">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>

                        </div>

                        {{-- Hint when value_type = 2 (số) --}}
                        <p x-show="cond.value_type == 2 || cond.value_type == 3"
                           class="text-xs text-amber-600/80">
                            ⚡ Kiểu số: so sánh sẽ dùng giá trị số, không phải chuỗi
                        </p>
                    </div>
                </template>
            </div>

            <div class="flex items-center justify-between pt-2">
                <button type="button" @click="prevStep()" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Trigger
                </button>
                <button type="button" @click="nextStep()" class="btn btn-primary btn-sm gap-1.5">
                    Tiếp theo: Hành động
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════
             BƯỚC 4 — Hành động (Steps)
        ════════════════════════════════════════════════════════════════════ --}}
        <div x-show="currentStep === 4" class="p-6 space-y-3">

            <div class="flex items-center justify-between mb-1">
                <div>
                    <h2 class="card-title text-base">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Hành động thực thi
                    </h2>
                    <p class="text-xs text-base-content/50 mt-0.5">Các bước sẽ chạy tuần tự khi trigger kích hoạt.</p>
                </div>
                <button type="button" @click="addStep()"
                        class="btn btn-outline btn-xs gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Thêm bước
                </button>
            </div>

            {{-- Empty state --}}
            <template x-if="form.steps.length === 0">
                <div class="empty-state py-10 border-2 border-dashed border-base-200 rounded-xl">
                    <svg class="empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="empty-title">Chưa có bước nào</p>
                    <p class="empty-desc">Thêm ít nhất một hành động để workflow thực thi khi trigger kích hoạt.</p>
                    <button type="button" @click="addStep()" class="btn btn-outline btn-xs mt-2">Thêm bước đầu tiên</button>
                </div>
            </template>

            {{-- Step cards --}}
            <template x-for="(step, si) in form.steps" :key="si">
                <div class="border border-base-200 rounded-xl bg-base-100 overflow-hidden">

                    {{-- Step card header --}}
                    <div class="flex items-center gap-3 px-4 py-2.5 bg-base-200/40 border-b border-base-200">
                        {{-- Step number badge --}}
                        <span class="flex items-center justify-center w-5 h-5 rounded-full bg-primary text-primary-content text-xs font-bold shrink-0"
                              x-text="si + 1"></span>

                        {{-- Step type badge --}}
                        <span class="badge badge-xs"
                              :class="step.step_type === 2 ? 'badge-warning' : (step.step_type === 3 ? 'badge-ghost' : 'badge-primary badge-outline')"
                              x-text="step.step_type === 2 ? '👤 Chờ phê duyệt' : (step.step_type === 3 ? '⚙ Control' : '🤖 Tự động')">
                        </span>

                        {{-- Action type badge --}}
                        <span class="badge badge-ghost badge-xs font-mono" x-show="step.action_type"
                              x-text="step.action_type"></span>

                        {{-- Halt indicator --}}
                        <span class="badge badge-error badge-xs gap-0.5" x-show="step.halt_on_fail" title="Workflow dừng nếu bước này thất bại">
                            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>
                            </svg>
                            Halt on fail
                        </span>

                        {{-- Output key badge --}}
                        <span class="badge badge-info badge-xs font-mono" x-show="step.step_output_key"
                              x-text="'{ctx.' + step.step_output_key + '}'"></span>

                        <div class="ml-auto flex gap-1">
                            {{-- Move up --}}
                            <button type="button" @click="moveStep(si, -1)" :disabled="si === 0"
                                    class="btn btn-ghost btn-xs btn-square opacity-40 hover:opacity-100 disabled:opacity-20" title="Lên">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                </svg>
                            </button>
                            {{-- Move down --}}
                            <button type="button" @click="moveStep(si, 1)" :disabled="si === form.steps.length - 1"
                                    class="btn btn-ghost btn-xs btn-square opacity-40 hover:opacity-100 disabled:opacity-20" title="Xuống">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            {{-- Remove --}}
                            <button type="button" @click="removeStep(si)"
                                    class="btn btn-ghost btn-xs btn-square text-error/40 hover:text-error" title="Xoá bước">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Step card body --}}
                    <div class="p-4 space-y-4">

                        {{-- Row 1: action_type + step_label + delay --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                            <div class="form-control sm:col-span-1">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text text-sm font-medium">Hành động <span class="text-error">*</span></span>
                                </label>
                                <select :name="'steps[' + si + '][action_type]'" x-model="step.action_type"
                                        @change="onStepActionChange(step)"
                                        class="select select-bordered select-sm w-full">
                                    <option value="">— Chọn —</option>
                                    <template x-for="[mod, actions] in Object.entries(meta.action_groups ?? {})" :key="mod">
                                        <optgroup :label="mod">
                                            <template x-for="a in actions" :key="a.type">
                                                <option :value="a.type" x-text="a.label"></option>
                                            </template>
                                        </optgroup>
                                    </template>
                                </select>
                            </div>

                            <div class="form-control sm:col-span-1">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text text-sm font-medium">Nhãn bước</span>
                                    <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                                </label>
                                <input type="text" :name="'steps[' + si + '][step_label]'" x-model="step.step_label"
                                       placeholder="VD: Gửi email xác nhận..." class="input input-bordered input-sm w-full">
                            </div>

                            <div class="form-control sm:col-span-1">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text text-sm font-medium">Delay</span>
                                    <span class="label-text-alt text-xs text-base-content/40">phút, 0 = ngay</span>
                                </label>
                                <input type="number" :name="'steps[' + si + '][delay_minutes]'"
                                       x-model.number="step.delay_minutes" min="0"
                                       class="input input-bordered input-sm w-full font-mono" placeholder="0">
                            </div>

                        </div>

                        {{-- step_type (hidden, auto-set from action_type) --}}
                        <input type="hidden" :name="'steps[' + si + '][step_type]'" :value="step.step_type">

                        {{-- Dynamic action_config fields --}}
                        <template x-if="stepConfigFields(step.action_type).length > 0">
                            <div class="space-y-3 pt-3 border-t border-base-100">
                                <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">Cấu hình hành động</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <template x-for="field in stepConfigFields(step.action_type)" :key="field.key">
                                        <div class="form-control"
                                             :class="field.type === 'textarea' ? 'sm:col-span-2' : ''">
                                            <label class="label py-0 pb-1.5">
                                                <span class="label-text text-sm font-medium">
                                                    <span x-text="field.label"></span>
                                                    <span class="text-error" x-show="field.required !== false">*</span>
                                                </span>
                                                <span x-show="field.required === false"
                                                      class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                                            </label>

                                            {{-- Textarea --}}
                                            <template x-if="field.type === 'textarea'">
                                                <textarea :name="'steps[' + si + '][action_config][' + field.key + ']'"
                                                          x-model="step.action_config[field.key]"
                                                          rows="3"
                                                          :placeholder="field.hint ?? ''"
                                                          class="textarea textarea-bordered textarea-sm w-full font-mono text-xs"></textarea>
                                            </template>

                                            {{-- Select with static options --}}
                                            <template x-if="field.type === 'select' && field.options">
                                                <select :name="'steps[' + si + '][action_config][' + field.key + ']'"
                                                        x-model="step.action_config[field.key]"
                                                        class="select select-bordered select-sm w-full">
                                                    <option value="">— Chọn —</option>
                                                    <template x-for="opt in field.options" :key="opt.value">
                                                        <option :value="opt.value" x-text="opt.label"></option>
                                                    </template>
                                                </select>
                                            </template>

                                            {{-- Password --}}
                                            <template x-if="field.type === 'password'">
                                                <input type="password"
                                                       :name="'steps[' + si + '][action_config][' + field.key + ']'"
                                                       x-model="step.action_config[field.key]"
                                                       :placeholder="field.hint ?? ''"
                                                       class="input input-bordered input-sm w-full font-mono">
                                            </template>

                                            {{-- Default: text / number / url --}}
                                            <template x-if="!['textarea','select','password'].includes(field.type)">
                                                <input :type="field.type === 'number' ? 'number' : (field.type === 'url' ? 'url' : 'text')"
                                                       :name="'steps[' + si + '][action_config][' + field.key + ']'"
                                                       x-model="step.action_config[field.key]"
                                                       :placeholder="field.hint ?? ''"
                                                       class="input input-bordered input-sm w-full">
                                            </template>

                                            <p x-show="field.hint && field.type !== 'textarea'"
                                               class="mt-1 text-xs text-base-content/40" x-text="field.hint"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Webhook headers --}}
                        <template x-if="step.action_type === 'webhook.call'">
                            <div class="space-y-2 pt-3 border-t border-base-100">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">Custom HTTP Headers</p>
                                    <button type="button" @click="addHeader(step)" class="btn btn-ghost btn-xs gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Header
                                    </button>
                                </div>
                                <template x-for="(h, hi) in step.headers" :key="hi">
                                    <div class="flex gap-2 items-center">
                                        <input type="text" :name="'steps[' + si + '][headers][' + hi + '][header_key]'"
                                               x-model="h.header_key" placeholder="X-Api-Key"
                                               class="input input-bordered input-xs flex-1 font-mono">
                                        <input type="text" :name="'steps[' + si + '][headers][' + hi + '][header_value]'"
                                               x-model="h.header_value" placeholder="Value hoặc {var.KEY}"
                                               class="input input-bordered input-xs flex-1">
                                        <button type="button" @click="step.headers.splice(hi, 1)"
                                                class="btn btn-ghost btn-xs btn-square text-error/40 hover:text-error">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- ─── Advanced section (collapsible) ──────────────── --}}
                        <div class="pt-2 border-t border-base-100">
                            <button type="button" @click="step._show_advanced = !step._show_advanced"
                                    class="flex items-center gap-1.5 text-xs text-base-content/50 hover:text-base-content transition-colors">
                                <svg class="w-3.5 h-3.5 transition-transform" :class="step._show_advanced ? 'rotate-90' : ''"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                Nâng cao
                                <span class="badge badge-xs badge-ghost ml-1"
                                      x-show="step.halt_on_fail || step.step_output_key || step._show_cond">đã cấu hình</span>
                            </button>

                            <div x-show="step._show_advanced" x-collapse class="mt-3 space-y-3">

                                {{-- halt_on_fail --}}
                                <label class="flex items-start gap-2.5 cursor-pointer select-none group p-2.5 rounded-xl border hover:border-error/40 transition-colors"
                                       :class="step.halt_on_fail ? 'border-error/40 bg-error/5' : 'border-base-200'">
                                    <input type="checkbox" :name="'steps[' + si + '][halt_on_fail]'"
                                           x-model="step.halt_on_fail" value="1"
                                           class="checkbox checkbox-xs checkbox-error mt-0.5 shrink-0">
                                    <div>
                                        <span class="text-xs font-medium"
                                              :class="step.halt_on_fail ? 'text-error' : ''">Dừng workflow nếu bước này thất bại</span>
                                        <p class="text-xs text-base-content/40 mt-0.5">
                                            Nếu bật: khi bước này lỗi, tất cả bước sau sẽ bị bỏ qua (Halted).
                                            Nếu tắt: workflow tiếp tục dù bước này lỗi.
                                        </p>
                                    </div>
                                </label>

                                {{-- step_output_key --}}
                                <div class="form-control">
                                    <label class="label py-0 pb-1.5">
                                        <span class="label-text text-xs font-medium">Pipeline output key</span>
                                        <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                                    </label>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs text-base-content/40 font-mono">{ctx.</span>
                                        <input type="text"
                                               :name="'steps[' + si + '][step_output_key]'"
                                               x-model="step.step_output_key"
                                               placeholder="analysis"
                                               pattern="[a-z0-9_]*"
                                               class="input input-bordered input-xs font-mono flex-1">
                                        <span class="text-xs text-base-content/40 font-mono">}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-base-content/40">
                                        Output của bước này sẽ lưu vào RunContext với key trên. Bước sau có thể dùng <code class="text-primary">{ctx.<span x-text="step.step_output_key || 'key'"></span>}</code> trong template.
                                    </p>
                                </div>

                                {{-- Per-step condition (condition_config) --}}
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="text-xs font-medium">Điều kiện bỏ qua bước</span>
                                            <p class="text-xs text-base-content/40">Nếu điều kiện không thỏa → bước bị skip (không lỗi workflow).</p>
                                        </div>
                                        <button type="button" @click="addStepCondition(step)"
                                                class="btn btn-ghost btn-xs gap-1">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Thêm
                                        </button>
                                    </div>

                                    <template x-if="step.condition_config.conditions.length > 1">
                                        <select :name="'steps[' + si + '][condition_config][match]'"
                                                x-model="step.condition_config.match"
                                                class="select select-bordered select-xs w-32">
                                            <option value="ALL">Tất cả (AND)</option>
                                            <option value="ANY">Ít nhất 1 (OR)</option>
                                        </select>
                                    </template>

                                    <template x-for="(sc, sci) in step.condition_config.conditions" :key="sci">
                                        <div class="flex flex-wrap gap-1.5 items-center p-2 bg-base-200/70 rounded-lg">
                                            <input type="text"
                                                   :name="'steps[' + si + '][condition_config][conditions][' + sci + '][field]'"
                                                   x-model="sc.field"
                                                   placeholder="ctx.score / task.decision / extra.budget"
                                                   class="input input-bordered input-xs font-mono flex-1 min-w-32">
                                            <select :name="'steps[' + si + '][condition_config][conditions][' + sci + '][operator]'"
                                                    x-model="sc.operator"
                                                    class="select select-bordered select-xs w-20">
                                                <option value="=">=</option>
                                                <option value="!=">≠</option>
                                                <option value=">">></option>
                                                <option value=">=">&ge;</option>
                                                <option value="<"><</option>
                                                <option value="<=">&le;</option>
                                                <option value="contains">chứa</option>
                                                <option value="is_empty">trống</option>
                                                <option value="is_not_empty">có giá trị</option>
                                            </select>
                                            <input type="text"
                                                   :name="'steps[' + si + '][condition_config][conditions][' + sci + '][value]'"
                                                   x-model="sc.value"
                                                   placeholder="70 / approve / true"
                                                   class="input input-bordered input-xs flex-1 min-w-20"
                                                   x-show="!['is_empty','is_not_empty'].includes(sc.operator)">
                                            <select :name="'steps[' + si + '][condition_config][conditions][' + sci + '][type]'"
                                                    x-model="sc.type"
                                                    class="select select-bordered select-xs w-20"
                                                    title="Kiểu dữ liệu">
                                                <option value="string">text</option>
                                                <option value="integer">số</option>
                                                <option value="float">thực</option>
                                                <option value="boolean">bool</option>
                                            </select>
                                            <button type="button" @click="step.condition_config.conditions.splice(sci, 1)"
                                                    class="btn btn-ghost btn-xs btn-square text-error/40 hover:text-error">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                            </div>{{-- end advanced --}}
                        </div>

                    </div>{{-- end step body --}}
                </div>{{-- end step card --}}
            </template>

            <div class="flex items-center justify-between pt-2">
                <button type="button" @click="prevStep()" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Điều kiện
                </button>
                <button type="button" @click="nextStep()" class="btn btn-primary btn-sm gap-1.5"
                        :class="form.steps.length === 0 ? 'btn-outline' : ''">
                    <template x-if="form.steps.length === 0">
                        <span>Bỏ qua (không có hành động)</span>
                    </template>
                    <template x-if="form.steps.length > 0">
                        <span>Tiếp theo: Cài đặt</span>
                    </template>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════
             BƯỚC 5 — Cài đặt & Xem lại
        ════════════════════════════════════════════════════════════════════ --}}
        <div x-show="currentStep === 5" class="p-6 space-y-5">

            <h2 class="card-title text-base mb-1">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Cài đặt & Xem lại
            </h2>

            {{-- Cooldown --}}
            <div class="form-control max-w-sm">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Giới hạn tần suất (Cooldown)</span>
                </label>
                <select name="cooldown_type" x-model.number="form.cooldown_type"
                        class="select select-bordered select-sm w-full @error('cooldown_type') select-error @enderror">
                    <template x-for="c in meta.cooldown_types ?? []" :key="c.value">
                        <option :value="c.value" x-text="c.label"></option>
                    </template>
                </select>
                <p class="mt-1 text-xs text-base-content/40">Tránh workflow chạy quá nhiều lần cho cùng một đối tượng.</p>
                @error('cooldown_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            {{-- Summary card --}}
            <div class="rounded-xl bg-base-200/50 border border-base-200 p-4 space-y-3">
                <p class="text-xs font-bold text-base-content/50 uppercase tracking-wide">Tóm tắt cấu hình</p>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                    <span class="text-base-content/50">Tên:</span>
                    <span class="font-medium truncate" x-text="form.name || '—'"></span>

                    <span class="text-base-content/50">Trigger:</span>
                    <span class="font-mono truncate" x-text="form.trigger_type || '—'"></span>

                    <span class="text-base-content/50">Ưu tiên:</span>
                    <span x-text="priorityLabel(form.priority)"></span>

                    <span class="text-base-content/50">Điều kiện:</span>
                    <span x-text="form.conditions.length > 0 ? form.conditions.length + ' điều kiện' : 'Không có (chạy với mọi trigger)'"></span>

                    <span class="text-base-content/50">Hành động:</span>
                    <span x-text="form.steps.length > 0 ? form.steps.length + ' bước' : 'Không có'"></span>

                    <span class="text-base-content/50">Trạng thái:</span>
                    <span :class="form.is_active ? 'text-success font-semibold' : 'text-base-content/50'"
                          x-text="form.is_active ? '✓ Sẽ kích hoạt ngay' : '— Tắt (kích hoạt thủ công sau)'"></span>
                </div>

                {{-- Steps quick view --}}
                <template x-if="form.steps.length > 0">
                    <div class="mt-2 space-y-1">
                        <p class="text-xs font-semibold text-base-content/40">Danh sách bước:</p>
                        <template x-for="(s, si) in form.steps" :key="si">
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-4 h-4 rounded-full bg-base-300 text-base-content/60 flex items-center justify-center text-xs font-bold shrink-0"
                                      x-text="si + 1"></span>
                                <span class="font-mono text-primary/80" x-text="s.action_type || '(chưa chọn)'"></span>
                                <span x-show="s.step_label" class="text-base-content/50 truncate" x-text="'— ' + s.step_label"></span>
                                <span x-show="s.halt_on_fail" class="badge badge-error badge-xs">halt</span>
                                <span x-show="s.step_output_key" class="badge badge-info badge-xs font-mono"
                                      x-text="'{ctx.' + s.step_output_key + '}'"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div class="flex items-center justify-between pt-2">
                <button type="button" @click="prevStep()" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Hành động
                </button>
                <button type="button" @click="submitForm()" :disabled="loading"
                        class="btn btn-primary btn-sm gap-1.5 min-w-32">
                    <span x-show="loading" class="loading loading-spinner loading-xs"></span>
                    <svg x-show="!loading" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ $isEdit ? 'Lưu thay đổi' : 'Tạo Workflow' }}
                </button>
            </div>
        </div>

    </div>{{-- end card --}}

    <p class="text-center text-xs text-base-content/30 mt-3">
        <span class="text-error">*</span> là trường bắt buộc
    </p>

</form>
</div>

@push('styles')
    @vite(['Modules/WorkflowAutomation/resources/assets/sass/workflow-automation.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'Modules/WorkflowAutomation/resources/assets/js/workflow-automation.js',
    ], 'build/backend')
@endpush
