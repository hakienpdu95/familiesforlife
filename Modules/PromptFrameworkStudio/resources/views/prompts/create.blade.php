@extends('layouts.backend')
@section('title', 'Tạo prompt mới')

@section('content')

<div class="flex items-center justify-between mb-2">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Tạo yêu cầu mới cho AI</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Chọn 1 mẫu phù hợp, điền vào từng ô như đang mô tả công việc cho một trợ lý — hệ thống tự ghép thành 1 yêu cầu hoàn chỉnh, kèm sẵn ngữ cảnh biên tập của chuyên mục bạn chọn (độc giả, nỗi đau, giọng văn) nên bạn không phải gõ lại mỗi lần</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.promptstudio.library') }}" class="btn btn-ghost btn-sm">Thư viện mẫu</a>
        <a href="{{ route('backend.promptstudio.prompts.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
    </div>
</div>

<form method="POST" action="{{ route('backend.promptstudio.prompts.store') }}"
      x-data="promptGenerator(
          @js(config('prompt_framework_studio.frameworks')),
          @js($preselectedKey),
          null,
          {{ Js::from([
              'editorialContextUrlTemplate' => route('backend.api.promptstudio.editorial-context', ['category' => '__UUID__']),
              'initialCategoryUuid' => old('post_category_uuid'),
          ]) }}
      )">
    @csrf

    {{-- Bước 1: lưới chọn mẫu — thẻ lớn, hiện rõ "phù hợp khi" để không cần biết thuật ngữ kỹ thuật --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4" x-show="!selectedKey || showFrameworkPicker" x-cloak>
        <div class="card-body p-5">
            <h2 class="card-title text-base mb-3">1. Chọn mẫu phù hợp với việc bạn đang cần làm</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                <template x-for="(fw, key) in frameworks" :key="key">
                    <button type="button" @click="select(key); showFrameworkPicker = false"
                            class="text-left p-3 rounded-lg border-2 transition-colors hover:border-primary/50"
                            :class="selectedKey === key ? 'border-primary bg-primary/5' : 'border-base-200'">
                        <div class="flex items-center justify-between gap-2">
                            <span class="badge badge-primary badge-outline badge-sm font-mono" x-text="fw.name"></span>
                            <svg x-show="selectedKey === key" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <p class="text-xs font-medium text-base-content mt-2" x-text="fw.best_for"></p>
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- Bước 2: form field động theo mẫu đã chọn --}}
    <template x-if="selectedKey">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="card-title text-base">
                        2. Điền nội dung —
                        <span class="badge badge-primary badge-outline font-mono" x-text="selectedFramework.name"></span>
                    </h2>
                    <button type="button" class="btn btn-ghost btn-xs" @click="showFrameworkPicker = true">Đổi mẫu khác</button>
                </div>
                <p class="text-xs text-base-content/40 -mt-2" x-text="selectedFramework.description"></p>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Tên gọi cho prompt này <span class="text-error">*</span></span>
                        <span class="label-text-alt text-xs text-base-content/40">Để bạn dễ tìm lại sau này</span>
                    </label>
                    <input type="text" name="label" value="{{ old('label') }}" maxlength="150" required
                           class="input input-bordered input-sm w-full @error('label') input-error @enderror"
                           placeholder="VD: Mở bài blog tài chính gia đình">
                    @error('label')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="divider my-1"></div>

                <input type="hidden" name="framework_key" :value="selectedKey">
                @include('promptframeworkstudio::prompts.partials.field-form', ['selectedCategoryUuid' => null])

                <div class="pt-2">
                    <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Sinh prompt
                    </button>
                </div>
            </div>
        </div>
    </template>
</form>

@endsection

@push('scripts')
    @vite(['Modules/PromptFrameworkStudio/resources/assets/js/prompt-framework-studio.js'], 'build/backend')
@endpush
