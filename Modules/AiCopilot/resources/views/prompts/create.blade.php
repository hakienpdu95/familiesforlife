@extends('layouts.backend')
@section('title', 'Tạo Prompt mới')

@section('content')
<div x-data="{
    tab: 'basic',
    tabFields: {
        basic:     ['agent_id', 'name', 'organization_id'],
        content:   ['system_prompt', 'user_template'],
        variables: [],
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.includes(f)).length;
    },
    init() {
        const order = ['basic', 'content', 'variables'];
        for (const t of order) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    },
    variables: {{ Js::from(old('variables_schema', [])) }},
    addVar()     { this.variables.push({ key: '', type: 'string', required: true }) },
    removeVar(i) { this.variables.splice(i, 1) },
}">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Tạo Prompt mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Template prompt cho AI agent</p>
    </div>
    <a href="{{ route('ai.prompts.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
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

<form method="POST" action="{{ route('ai.prompts.store') }}" novalidate data-ai-prompt-form>
    @csrf

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
                        Thông tin
                        <span x-show="errCount('basic') > 0" x-text="errCount('basic')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'content'"
                            @click="tab = 'content'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'content'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Nội dung Prompt
                        <span x-show="errCount('content') > 0" x-text="errCount('content')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'variables'"
                            @click="tab = 'variables'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'variables'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Biến
                    </button>

                </nav>
            </div>

            <div class="p-6">

                {{-- Panel: Thông tin --}}
                <div x-show="tab === 'basic'" data-tab-label="Thông tin" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Tổ chức --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tổ chức <span class="text-error">*</span></span>
                            </label>
                            @if($orgLocked)
                                <input type="hidden" name="organization_id" value="{{ $organizations->first()->id }}">
                                <input type="text" value="{{ $organizations->first()->name }}" readonly
                                       class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
                                <p class="mt-1 text-xs text-base-content/40">Xác định từ tài khoản của bạn.</p>
                            @else
                                <select id="ts-organization_id" name="organization_id"
                                        class="select select-bordered select-sm w-full ts-init @error('organization_id') select-error @enderror"
                                        data-ts-placeholder="— Chọn tổ chức —"
                                        data-req="Vui lòng chọn tổ chức">
                                    <option value="">— Chọn tổ chức —</option>
                                    @foreach($organizations as $org)
                                    <option value="{{ $org->id }}" {{ old('organization_id', $defaultOrgId ?? '') == $org->id ? 'selected' : '' }}>
                                        {{ $org->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            @endif
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Agent <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-agent-id" name="agent_id"
                                    data-req="Vui lòng chọn agent"
                                    class="select select-bordered select-sm w-full ts-init @error('agent_id') select-error @enderror"
                                    data-ts-placeholder="— Chọn agent —">
                                <option value="">— Chọn agent —</option>
                                @foreach($agents as $ag)
                                <option value="{{ $ag->id }}"
                                        {{ old('agent_id', $selectedAgentId ?? '') == $ag->id ? 'selected' : '' }}>
                                    {{ $ag->name }} ({{ $ag->slug }})@if($ag->is_system) [system]@endif
                                </option>
                                @endforeach
                            </select>
                            @error('agent_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên prompt <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   data-req="Vui lòng nhập tên prompt"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   placeholder="VD: Phân tích KPI v2 — Tiếng Việt">
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mô tả</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                            </label>
                            <input type="text" name="description" value="{{ old('description') }}"
                                   class="input input-bordered input-sm w-full @error('description') input-error @enderror"
                                   placeholder="VD: Phân tích KPI nhân viên bằng tiếng Việt, có ví dụ cụ thể">
                            @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'content'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Nội dung Prompt
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: Nội dung Prompt --}}
                <div x-show="tab === 'content'" data-tab-label="Nội dung Prompt" class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">System Prompt <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">Vai trò AI, hướng dẫn tổng quát</span>
                        </label>
                        <textarea name="system_prompt" rows="7"
                                  data-req="Vui lòng nhập system prompt"
                                  class="textarea textarea-bordered textarea-sm w-full font-mono text-sm @error('system_prompt') textarea-error @enderror"
                                  placeholder="VD: Bạn là chuyên gia phân tích KPI. Hãy phân tích kết quả và đưa ra nhận xét chi tiết...">{{ old('system_prompt') }}</textarea>
                        @error('system_prompt')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">User Template <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">Dùng <code class="font-mono bg-base-200 px-1 rounded">&#123;&#123;tên_biến&#125;&#125;</code> cho nội dung động</span>
                        </label>
                        <textarea name="user_template" rows="9"
                                  data-req="Vui lòng nhập user template"
                                  class="textarea textarea-bordered textarea-sm w-full font-mono text-sm @error('user_template') textarea-error @enderror"
                                  placeholder="VD: Phân tích KPI sau:&#10;Nhân viên: &#123;&#123;employee_name&#125;&#125;&#10;Mục tiêu: &#123;&#123;target_value&#125;&#125;&#10;Kết quả: &#123;&#123;actual_value&#125;&#125;">{{ old('user_template') }}</textarea>
                        @error('user_template')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'basic'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Thông tin
                        </button>
                        <button type="button" @click="tab = 'variables'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Biến
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: Biến --}}
                <div x-show="tab === 'variables'" data-tab-label="Biến" class="space-y-4">

                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="card-title text-base">
                                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                </svg>
                                Khai báo biến
                            </h2>
                            <p class="text-xs text-base-content/50 mt-1">Biến được dùng trong user template dạng <code class="font-mono bg-base-200 px-1 rounded text-xs">&#123;&#123;tên_biến&#125;&#125;</code></p>
                        </div>
                        <button type="button" @click="addVar()" class="btn btn-primary btn-sm gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Thêm biến
                        </button>
                    </div>

                    <div class="space-y-2">
                        <template x-if="variables.length > 0">
                            <div class="grid grid-cols-[1fr_120px_100px_32px] gap-2 px-1 pb-1">
                                <span class="text-xs font-medium text-base-content/50">Tên biến (key)</span>
                                <span class="text-xs font-medium text-base-content/50">Kiểu dữ liệu</span>
                                <span class="text-xs font-medium text-base-content/50">Bắt buộc</span>
                                <span></span>
                            </div>
                        </template>
                        <template x-for="(v, i) in variables" :key="i">
                            <div class="grid grid-cols-[1fr_120px_100px_32px] gap-2 items-center">
                                <input type="text" :name="`variables_schema[${i}][key]`" x-model="v.key"
                                       class="input input-bordered input-xs font-mono w-full"
                                       placeholder="VD: employee_name">
                                <select :name="`variables_schema[${i}][type]`" x-model="v.type"
                                        class="select select-bordered select-xs w-full">
                                    <option value="string">string</option>
                                    <option value="text">text</option>
                                    <option value="integer">integer</option>
                                    <option value="boolean">boolean</option>
                                </select>
                                <label class="flex items-center gap-1.5 cursor-pointer justify-center">
                                    <input type="checkbox" :name="`variables_schema[${i}][required]`" value="1"
                                           x-model="v.required" class="checkbox checkbox-xs checkbox-primary">
                                    <span class="text-xs text-base-content/70">Required</span>
                                </label>
                                <button type="button" @click="removeVar(i)"
                                        class="btn btn-ghost btn-xs text-error p-0 w-8 h-8">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>

                        <template x-if="variables.length === 0">
                            <p class="text-sm text-base-content/40 italic py-4 text-center">Chưa có biến nào. Nhấn "Thêm biến" để khai báo.</p>
                        </template>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'content'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Nội dung Prompt
                        </button>
                        <span class="text-xs text-base-content/40">Nhấn <strong>Tạo mới</strong> ở bên phải khi xong</span>
                    </div>

                </div>

            </div>{{-- /panels --}}
        </div>{{-- /card chính --}}

        {{-- Sidebar --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            {{-- Xuất bản --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="space-y-3 mb-4">
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Kích hoạt</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Prompt sẵn sàng được dùng</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="hidden" name="is_default" value="0">
                            <input type="checkbox" name="is_default" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('is_default') == '1' ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Đặt làm Default</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Prompt mặc định cho agent này</p>
                            </div>
                        </label>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('ai.prompts.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tạo mới
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

                </div>
            </div>

            {{-- Cú pháp biến --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-2">Cú pháp biến</p>
                    <p class="text-xs text-base-content/60 mb-1.5">Trong user template, dùng:</p>
                    <code class="text-xs bg-base-200 px-2 py-1.5 rounded block font-mono">&#123;&#123;tên_biến&#125;&#125;</code>
                    <p class="text-xs text-base-content/50 mt-2">VD: <code class="font-mono text-primary text-xs">&#123;&#123;employee_name&#125;&#125;</code></p>
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
