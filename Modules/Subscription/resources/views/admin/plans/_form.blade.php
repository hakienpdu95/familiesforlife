{{--
  Shared plan form partial — Tab-Based Form (spec v5).
  Variables: $plan (Plan|null), $featureSlugs (array), $action (string), $method (string)
--}}

@php
    $isEdit = isset($plan) && $plan !== null;

    $featureNames = [
        'module.task'               => 'Module: Công việc',
        'module.sop'                => 'Module: SOP',
        'module.hr'                 => 'Module: Nhân sự',
        'module.crm'                => 'Module: CRM / Lead',
        'module.workflow'           => 'Module: Workflow',
        'module.ai'                 => 'Module: AI',
        'module.recruitment'        => 'Module: Tuyển dụng',
        'module.assessment'         => 'Module: Assessment',
        'module.project'            => 'Module: Dự án',
        'module.kc'                 => 'Module: Kho tri thức',
        'module.marketplace'        => 'Module: Marketplace',
        'limit.employees'           => 'Giới hạn: Nhân viên',
        'limit.members'             => 'Giới hạn: Người dùng',
        'limit.workflows'           => 'Giới hạn: Workflow',
        'limit.projects'            => 'Giới hạn: Dự án',
        'limit.storage_gb'          => 'Giới hạn: Dung lượng (GB)',
        'flag.api_access'           => 'Flag: API Access',
        'flag.audit_log'            => 'Flag: Audit Log',
        'flag.advanced_reports'     => 'Flag: Báo cáo nâng cao',
        'flag.sso'                  => 'Flag: SSO',
        'flag.white_label'          => 'Flag: White Label',
        'flag.custom_domain'        => 'Flag: Custom Domain',
        'quota.ai_requests'         => 'Quota: AI requests / tháng',
        'quota.workflow_runs'       => 'Quota: Workflow runs / tháng',
        'quota.email_notifications' => 'Quota: Email notifications / tháng',
    ];

    $existingFeatures = $isEdit ? $plan->features->keyBy('slug') : collect();
@endphp

<div x-data="{
    tab: 'basic',
    tabFields: {
        basic:    ['name', 'slug'],
        pricing:  ['price', 'currency'],
        features: [],
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.includes(f)).length;
    },
    init() {
        const order = Object.keys(this.tabFields);
        for (const t of order) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    }
}">

<form method="POST" action="{{ $action }}" novalidate data-plan-form>
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính: tab nav + panels ─────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">

            {{-- Tab navigation --}}
            <div class="border-b border-base-200 px-6">
                <nav class="flex -mb-px" role="tablist" aria-label="Phần thông tin plan">

                    <button type="button" role="tab" :aria-selected="tab === 'basic'"
                            @click="tab = 'basic'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'basic'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Thông tin cơ bản
                        <span x-show="errCount('basic') > 0" x-text="errCount('basic')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'pricing'"
                            @click="tab = 'pricing'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'pricing'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Giá & Chu kỳ
                        <span x-show="errCount('pricing') > 0" x-text="errCount('pricing')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'features'"
                            @click="tab = 'features'"
                            class="flex items-center gap-1.5 px-1 py-4 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'features'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Features
                        <span x-show="errCount('features') > 0" x-text="errCount('features')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                </nav>
            </div>

            {{-- Tab panels --}}
            <div class="p-6">

                {{-- ── Tab 1: Thông tin cơ bản ─────────────────────── --}}
                <div x-show="tab === 'basic'" data-tab-label="Thông tin cơ bản" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Tên plan — full width --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên plan <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $plan?->name) }}"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   placeholder="VD: Gói Tăng trưởng"
                                   data-req="Vui lòng nhập tên plan">
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Slug — half (auto-fill từ tên) --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Slug <span class="text-error">*</span></span>
                                @if($isEdit)
                                    <span class="label-text-alt text-xs text-base-content/40">Thận trọng khi thay đổi</span>
                                @else
                                    <span class="label-text-alt text-xs text-base-content/40">Tự động tạo nếu để trống</span>
                                @endif
                            </label>
                            <input type="text" name="slug" value="{{ old('slug', $plan?->slug) }}"
                                   class="input input-bordered input-sm w-full font-mono @error('slug') input-error @enderror"
                                   placeholder="ten-plan-vd"
                                   data-req="Vui lòng nhập slug"
                                   {{ $isEdit ? 'readonly' : '' }}>
                            <p class="mt-1 text-xs text-base-content/40">
                                Chỉ dùng chữ thường, số và dấu <code class="bg-base-200 px-1 rounded">-</code>
                            </p>
                            @error('slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Tier — half --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tier <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-tier" name="tier"
                                    class="select select-bordered select-sm w-full ts-init @error('tier') select-error @enderror"
                                    data-ts-placeholder="— Chọn tier —">
                                <option value="">— Chọn tier —</option>
                                @foreach(['starter' => 'Starter (miễn phí)', 'growth' => 'Growth', 'scale' => 'Scale', 'enterprise' => 'Enterprise'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('tier', $plan?->tier ?? 'growth') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('tier')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Tag line — full width --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tag line</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tối đa 120 ký tự</span>
                            </label>
                            <input type="text" name="tag_line" value="{{ old('tag_line', $plan?->tag_line) }}"
                                   class="input input-bordered input-sm w-full @error('tag_line') input-error @enderror"
                                   placeholder="VD: Phổ biến nhất · Đề xuất" maxlength="120">
                            @error('tag_line')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Badge color — half --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Badge color</span>
                                <span class="label-text-alt text-xs text-base-content/40">DaisyUI class</span>
                            </label>
                            <input type="text" name="badge_color" value="{{ old('badge_color', $plan?->badge_color) }}"
                                   class="input input-bordered input-sm w-full font-mono @error('badge_color') input-error @enderror"
                                   placeholder="badge-primary">
                            <p class="mt-1 text-xs text-base-content/40">
                                VD: <code class="bg-base-200 px-1 rounded">badge-primary</code>, <code class="bg-base-200 px-1 rounded">badge-accent</code>
                            </p>
                            @error('badge_color')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    {{-- Mô tả — textarea ngoài grid --}}
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mô tả</span>
                        </label>
                        <textarea name="description" rows="3"
                                  class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                                  placeholder="Mô tả ngắn về gói dịch vụ...">{{ old('description', $plan?->description) }}</textarea>
                        @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Tab footer nav --}}
                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'pricing'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Giá & Chu kỳ
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- ── Tab 2: Giá & Chu kỳ ─────────────────────────── --}}
                <div x-show="tab === 'pricing'" data-tab-label="Giá & Chu kỳ" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Giá theo tháng --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Giá theo tháng <span class="text-error">*</span></span>
                                <span class="label-text-alt text-xs text-base-content/40">VND</span>
                            </label>
                            <input type="number" name="price" value="{{ old('price', $plan?->price ?? 0) }}"
                                   class="input input-bordered input-sm w-full @error('price') input-error @enderror"
                                   placeholder="VD: 500000"
                                   min="0" step="1000"
                                   data-req="Vui lòng nhập giá">
                            @error('price')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Giá theo năm --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Giá theo năm</span>
                                <span class="label-text-alt text-xs text-base-content/40">VND, tùy chọn</span>
                            </label>
                            <input type="number" name="annual_price" value="{{ old('annual_price', $plan?->annual_price) }}"
                                   class="input input-bordered input-sm w-full @error('annual_price') input-error @enderror"
                                   placeholder="Để trống nếu không có"
                                   min="0" step="1000">
                            @error('annual_price')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Đơn vị tiền tệ --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Đơn vị tiền tệ <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="currency" value="{{ old('currency', $plan?->currency ?? 'VND') }}"
                                   class="input input-bordered input-sm w-full font-mono @error('currency') input-error @enderror"
                                   placeholder="VND" maxlength="10">
                            @error('currency')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="divider my-4 text-xs text-base-content/30">Chu kỳ</div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Chu kỳ hóa đơn --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Chu kỳ hóa đơn <span class="text-error">*</span></span>
                            </label>
                            <div class="join w-full">
                                <input type="number" name="invoice_period"
                                       value="{{ old('invoice_period', $plan?->invoice_period ?? 1) }}"
                                       class="input input-bordered input-sm join-item w-24"
                                       min="1" placeholder="1">
                                <select name="invoice_interval"
                                        class="select select-bordered select-sm join-item flex-1">
                                    @foreach(['day' => 'Ngày', 'week' => 'Tuần', 'month' => 'Tháng', 'year' => 'Năm'] as $val => $lbl)
                                        <option value="{{ $val }}" {{ old('invoice_interval', $plan?->invoice_interval ?? 'month') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="mt-1 text-xs text-base-content/40">Số lượng + đơn vị, VD: 1 Tháng</p>
                        </div>

                        {{-- Trial period --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Thời gian dùng thử</span>
                                <span class="label-text-alt text-xs text-base-content/40">0 = không có</span>
                            </label>
                            <div class="join w-full">
                                <input type="number" name="trial_period"
                                       value="{{ old('trial_period', $plan?->trial_period ?? 0) }}"
                                       class="input input-bordered input-sm join-item w-24"
                                       min="0" placeholder="0">
                                <select name="trial_interval"
                                        class="select select-bordered select-sm join-item flex-1">
                                    @foreach(['day' => 'Ngày', 'week' => 'Tuần', 'month' => 'Tháng'] as $val => $lbl)
                                        <option value="{{ $val }}" {{ old('trial_interval', $plan?->trial_interval ?? 'day') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Grace period --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Grace period</span>
                                <span class="label-text-alt text-xs text-base-content/40">Gia hạn sau hết hạn</span>
                            </label>
                            <div class="join w-full">
                                <input type="number" name="grace_period"
                                       value="{{ old('grace_period', $plan?->grace_period ?? 3) }}"
                                       class="input input-bordered input-sm join-item w-24"
                                       min="0" placeholder="3">
                                <select name="grace_interval"
                                        class="select select-bordered select-sm join-item flex-1">
                                    @foreach(['day' => 'Ngày', 'week' => 'Tuần', 'month' => 'Tháng'] as $val => $lbl)
                                        <option value="{{ $val }}" {{ old('grace_interval', $plan?->grace_interval ?? 'day') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                    </div>

                    {{-- Tab footer nav --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'basic'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Thông tin cơ bản
                        </button>
                        <button type="button" @click="tab = 'features'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Features
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- ── Tab 3: Features ──────────────────────────────── --}}
                <div x-show="tab === 'features'" data-tab-label="Features" class="space-y-4">

                    <p class="text-xs text-base-content/50">
                        Định nghĩa tính năng cho plan.
                        <strong>Module / Flag</strong>: ✓ bật / ✗ tắt —
                        <strong>Limit</strong>: số nguyên (0 = vô hạn) —
                        <strong>Quota</strong>: số nguyên / tháng
                    </p>

                    <div class="overflow-x-auto">
                        <table class="table table-xs w-full">
                            <thead>
                                <tr class="text-xs text-base-content/60">
                                    <th class="w-52">Feature slug</th>
                                    <th>Tên hiển thị</th>
                                    <th class="w-28 text-right">Giá trị</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($featureNames as $slug => $fname)
                                    @php
                                        $existing = $existingFeatures->get($slug);
                                        $idx      = array_search($slug, array_keys($featureNames));
                                        $isFlag   = str_starts_with($slug, 'module.') || str_starts_with($slug, 'flag.');
                                        $isModule = str_starts_with($slug, 'module.');
                                    @endphp
                                    <tr class="{{ $isModule ? 'bg-base-200/30' : '' }}">
                                        <td>
                                            <input type="hidden" name="features[{{ $idx }}][slug]" value="{{ $slug }}">
                                            <input type="hidden" name="features[{{ $idx }}][name]" value="{{ $fname }}">
                                            <span class="font-mono text-xs text-base-content/70">{{ $slug }}</span>
                                        </td>
                                        <td class="text-xs">{{ $fname }}</td>
                                        <td class="text-right">
                                            @if ($isFlag)
                                                <select name="features[{{ $idx }}][value]"
                                                        class="select select-bordered select-xs w-20">
                                                    <option value="1" {{ old("features.$idx.value", $existing?->value) === '1' ? 'selected' : '' }}>✓ Bật</option>
                                                    <option value="0" {{ old("features.$idx.value", $existing?->value ?? '0') === '0' ? 'selected' : '' }}>✗ Tắt</option>
                                                </select>
                                            @else
                                                <input type="number" name="features[{{ $idx }}][value]"
                                                       value="{{ old("features.$idx.value", $existing?->value ?? '0') }}"
                                                       class="input input-bordered input-xs w-24 text-right"
                                                       min="0" placeholder="0">
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Tab footer nav --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'pricing'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Giá & Chu kỳ
                        </button>
                        <span class="text-xs text-base-content/40">
                            Nhấn <strong>{{ $isEdit ? 'Lưu lại' : 'Tạo plan' }}</strong> ở bên phải khi xong
                        </span>
                    </div>

                </div>

            </div>{{-- /p-6 --}}
        </div>{{-- /card chính --}}

        {{-- ── Sidebar Publish Block ──────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                        Xuất bản
                    </p>

                    {{-- Trạng thái kích hoạt --}}
                    <div class="space-y-3 mb-4">
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('is_active', $plan?->is_active ?? true) ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Kích hoạt</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Plan có thể được gán cho tổ chức</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="hidden" name="is_public" value="0">
                            <input type="checkbox" name="is_public" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('is_public', $plan?->is_public ?? true) ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Hiển thị công khai</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Hiện trên trang chọn gói</p>
                            </div>
                        </label>
                    </div>

                    @if ($isEdit)
                        {{-- Meta timestamps --}}
                        <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                            <span>Tạo {{ $plan->created_at->format('d/m/Y') }}</span>
                            <span>Sửa {{ $plan->updated_at->diffForHumans() }}</span>
                        </div>
                    @endif

                    {{-- Action buttons --}}
                    <div class="flex gap-2">
                        <a href="{{ route('subscription.admin.plans.index') }}"
                           class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            @if ($isEdit)
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Lưu lại
                            @else
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Tạo plan
                            @endif
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

                </div>
            </div>

        </div>{{-- /sidebar --}}

    </div>{{-- /grid --}}

</form>
</div>{{-- /x-data --}}
