@extends('layouts.backend')
@section('title', 'Sửa — '.$prompt->label)

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa &amp; sinh lại prompt</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Mẫu đang dùng: <span class="badge badge-primary badge-outline font-mono">{{ $framework['name'] }}</span>
            — không đổi sang mẫu khác được (bấm "Tạo prompt mới" ở danh sách nếu muốn dùng mẫu khác)
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.promptstudio.prompts.show', $prompt) }}" class="btn btn-ghost btn-sm">Xem</a>
        <a href="{{ route('backend.promptstudio.prompts.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
    </div>
</div>

{{-- §5.3 — data-confirm-regenerate: submit sẽ GHI ĐÈ rendered_prompt cũ, không versioning. --}}
<form method="POST" action="{{ route('backend.promptstudio.prompts.update', $prompt) }}" data-confirm-regenerate
      x-data="promptGenerator(@js(config('prompt_framework_studio.frameworks')), @js($prompt->framework_key), @js($prompt->field_values))">
    @csrf
    @method('PUT')

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5 space-y-4">

            <div class="form-control">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Tên prompt <span class="text-error">*</span></span>
                </label>
                <input type="text" name="label" value="{{ old('label', $prompt->label) }}" maxlength="150" required
                       class="input input-bordered input-sm w-full @error('label') input-error @enderror">
                @error('label')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <template x-for="field in selectedFramework.fields" :key="field.key">
                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">
                            <span x-text="field.label"></span>
                            <span x-show="field.required" class="text-error">&nbsp;*</span>
                        </span>
                    </label>
                    <textarea x-show="field.type === 'textarea'" x-model="values[field.key]" rows="3"
                              :placeholder="'VD: ' + (selectedFramework.example[field.key] || '')"
                              class="textarea textarea-bordered textarea-sm w-full placeholder:text-base-content/30"></textarea>
                    <input x-show="field.type === 'text'" x-model="values[field.key]" type="text"
                           :placeholder="'VD: ' + (selectedFramework.example[field.key] || '')"
                           class="input input-bordered input-sm w-full placeholder:text-base-content/30">
                </div>
            </template>

            {{-- framework_key KHÔNG gửi trong request — RegenerateGeneratedPromptAction luôn dùng
                 $prompt->framework_key hiện có (§5.3), không nhận từ input. --}}
            <template x-for="field in selectedFramework.fields" :key="field.key">
                <input type="hidden" :name="`field_values[${field.key}]`" :value="values[field.key]">
            </template>

            @error('field_values')<p class="text-xs text-error">{{ $message }}</p>@enderror

            <div class="pt-2">
                <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Sinh lại
                </button>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
    @vite(['Modules/PromptFrameworkStudio/resources/assets/js/prompt-framework-studio.js'], 'build/backend')
@endpush
