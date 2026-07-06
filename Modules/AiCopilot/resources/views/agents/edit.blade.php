@extends('layouts.backend')
@section('title', ($agent->is_system ? 'Cấu hình system agent' : 'Sửa agent') . ' — ' . $agent->name)

@section('content')
<script>
    window._aiAgentModels = {!! Js::from($modelsByProvider) !!};
</script>

<div x-data="{
    tab: 'basic',
    tabFields: {
        basic:  ['name', 'slug'],
        model:  ['provider', 'model', 'task_type'],
        params: [],
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.includes(f)).length;
    },
    init() {
        const order = ['basic', 'model', 'params'];
        for (const t of order) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    }
}">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">
            {{ $agent->is_system ? 'Cấu hình system agent' : 'Sửa agent' }}
        </h1>
        <p class="font-mono text-sm text-base-content/50 mt-0.5">{{ $agent->slug }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('ai.prompts.index', ['agent_id' => $agent->id]) }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Prompts
        </a>
        <a href="{{ route('ai.agents.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Quay lại
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

@if($agent->is_system)
<div class="alert alert-info py-3 px-4 mb-5 text-sm gap-3">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    System agent: chỉ có thể thay đổi tên, mô tả, tham số và trạng thái. Slug, provider, model, task type là bất biến.
</div>
@endif

<form method="POST" action="{{ route('ai.agents.update', $agent) }}" novalidate data-ai-agent-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- Card chính với tabs --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">

            {{-- Tab navigation --}}
            <div class="border-b border-base-200 px-6">
                <nav class="flex -mb-px" role="tablist" aria-label="Form sections">

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

                    <button type="button" role="tab" :aria-selected="tab === 'model'"
                            @click="tab = 'model'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'model'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Cấu hình AI
                        <span x-show="errCount('model') > 0" x-text="errCount('model')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'params'"
                            @click="tab = 'params'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'params'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Tham số
                    </button>

                </nav>
            </div>

            <div class="p-6">

                {{-- Panel: Thông tin cơ bản --}}
                <div x-show="tab === 'basic'" data-tab-label="Thông tin cơ bản" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Tổ chức: readonly sau khi tạo --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tổ chức</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không thể thay đổi</span>
                            </label>
                            <input type="text" value="{{ $orgName }}" readonly
                                   class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên agent <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $agent->name) }}"
                                   data-req="Vui lòng nhập tên agent"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   placeholder="VD: Custom KPI Reviewer">
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Slug <span class="text-error">*</span></span>
                                <span class="label-text-alt text-xs text-base-content/40">Thận trọng khi thay đổi</span>
                            </label>
                            @if($agent->is_system)
                            <input type="text" value="{{ $agent->slug }}"
                                   class="input input-bordered input-sm w-full font-mono bg-base-200 cursor-not-allowed" readonly>
                            <input type="hidden" name="slug" value="{{ $agent->slug }}">
                            <p class="mt-1 text-xs text-base-content/40">Slug của system agent là bất biến.</p>
                            @else
                            <input type="text" name="slug" value="{{ old('slug', $agent->slug) }}"
                                   data-req="Vui lòng nhập slug"
                                   class="input input-bordered input-sm w-full font-mono @error('slug') input-error @enderror"
                                   placeholder="VD: kpi.custom_review">
                            <p class="mt-1 text-xs text-base-content/40">Chỉ dùng chữ thường, số, dấu <code class="bg-base-200 px-1 rounded">.</code> và <code class="bg-base-200 px-1 rounded">_</code></p>
                            @endif
                            @error('slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mô tả</span>
                            <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                        </label>
                        <textarea name="description" rows="3"
                                  class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                                  placeholder="Mô tả ngắn về chức năng của agent...">{{ old('description', $agent->description) }}</textarea>
                        @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'model'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Cấu hình AI
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: Cấu hình AI --}}
                <div x-show="tab === 'model'" data-tab-label="Cấu hình AI" class="space-y-4">

                    @if($agent->is_system)
                    {{-- System agent: locked values displayed read-only --}}
                    <input type="hidden" name="provider"  value="{{ $agent->provider }}">
                    <input type="hidden" name="model"     value="{{ $agent->model }}">
                    <input type="hidden" name="task_type" value="{{ $agent->task_type }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide mb-1">Provider</p>
                            <p class="input input-bordered input-sm w-full font-mono bg-base-200 flex items-center cursor-not-allowed">{{ strtoupper($agent->provider) }}</p>
                        </div>
                        <div class="form-control">
                            <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide mb-1">Model</p>
                            <p class="input input-bordered input-sm w-full font-mono bg-base-200 flex items-center text-sm cursor-not-allowed">{{ $agent->model }}</p>
                        </div>
                        <div class="form-control sm:col-span-2">
                            <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide mb-1">Loại task</p>
                            <p class="input input-bordered input-sm w-full bg-base-200 flex items-center text-sm cursor-not-allowed">{{ $agent->task_type }}</p>
                        </div>
                    </div>
                    @else
                    {{-- Custom agent: editable selects --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Provider <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-provider" name="provider"
                                    data-req="Vui lòng chọn provider"
                                    class="select select-bordered select-sm w-full ts-init @error('provider') select-error @enderror"
                                    data-ts-placeholder="— Chọn provider —">
                                <option value="">— Chọn provider —</option>
                                @foreach($providers as $p)
                                <option value="{{ $p }}" {{ old('provider', $agent->provider) === $p ? 'selected' : '' }}>{{ strtoupper($p) }}</option>
                                @endforeach
                            </select>
                            @error('provider')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Model <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-model" name="model"
                                    data-req="Vui lòng chọn model"
                                    data-current-model="{{ old('model', $agent->model) }}"
                                    class="select select-bordered select-sm w-full @error('model') select-error @enderror">
                                <option value="">— Chọn model —</option>
                            </select>
                            <p class="mt-1 text-xs text-base-content/40">Danh sách model thay đổi theo provider đã chọn</p>
                            @error('model')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Loại task <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-task-type" name="task_type"
                                    data-req="Vui lòng chọn loại task"
                                    class="select select-bordered select-sm w-full ts-init @error('task_type') select-error @enderror"
                                    data-ts-placeholder="— Chọn loại task —">
                                <option value="">— Chọn loại task —</option>
                                @foreach($taskTypes as $t)
                                <option value="{{ $t }}" {{ old('task_type', $agent->task_type) === $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                            @error('task_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>
                    @endif

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'basic'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Thông tin cơ bản
                        </button>
                        <button type="button" @click="tab = 'params'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Tham số
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: Tham số --}}
                <div x-show="tab === 'params'" data-tab-label="Tham số" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Temperature</span>
                                <span class="label-text-alt text-xs text-base-content/40">0.0 – 2.0</span>
                            </label>
                            <input type="number" name="temperature" step="0.05" min="0" max="2"
                                   value="{{ old('temperature', $agent->temperature) }}"
                                   class="input input-bordered input-sm w-full @error('temperature') input-error @enderror">
                            <p class="mt-1 text-xs text-base-content/40">Độ ngẫu nhiên của output (0 = deterministic)</p>
                            @error('temperature')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Max tokens</span>
                                <span class="label-text-alt text-xs text-base-content/40">64 – 16 000</span>
                            </label>
                            <input type="number" name="max_tokens" min="64" max="16000"
                                   value="{{ old('max_tokens', $agent->max_tokens) }}"
                                   class="input input-bordered input-sm w-full @error('max_tokens') input-error @enderror">
                            @error('max_tokens')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Timeout</span>
                                <span class="label-text-alt text-xs text-base-content/40">giây (5 – 120)</span>
                            </label>
                            <input type="number" name="timeout_seconds" min="5" max="120"
                                   value="{{ old('timeout_seconds', $agent->timeout_seconds) }}"
                                   class="input input-bordered input-sm w-full @error('timeout_seconds') input-error @enderror">
                            @error('timeout_seconds')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'model'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Cấu hình AI
                        </button>
                        <span class="text-xs text-base-content/40">Nhấn <strong>Lưu lại</strong> ở bên phải khi xong</span>
                    </div>

                </div>

            </div>{{-- /panels --}}
        </div>{{-- /card chính --}}

        {{-- Sidebar --}}
        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="space-y-3 mb-3">
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('is_active', $agent->is_active) == '1' || old('is_active', $agent->is_active) === true ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Kích hoạt</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Agent sẵn sàng nhận task</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="hidden" name="sync_mode" value="0">
                            <input type="checkbox" name="sync_mode" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('sync_mode', $agent->sync_mode) == '1' || old('sync_mode', $agent->sync_mode) === true ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Sync mode</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Thực thi đồng bộ, không dùng queue</p>
                            </div>
                        </label>
                    </div>

                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $agent->created_at->format('d/m/Y') }}</span>
                        <span>Sửa {{ $agent->updated_at->diffForHumans() }}</span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('ai.agents.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Lưu lại
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
</div>
@endsection

@push('styles')
    @vite(['Modules/AiCopilot/resources/assets/sass/ai-copilot.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'Modules/AiCopilot/resources/assets/js/ai-copilot.js',
    ], 'build/backend')
@endpush
