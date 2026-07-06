@extends('layouts.backend')
@section('title', 'Tạo tag mới')


@section('content')
<div class="p-6">

    <div class="mb-5">
        <h1 class="text-xl font-bold">Tạo tag mới</h1>
        <p class="text-sm opacity-60 mt-0.5">Tag dùng để gắn nhãn cho tài liệu trong kho tri thức</p>
    </div>

    @if($errors->any())
    <div class="alert alert-error mb-5">
        <ul class="list-disc pl-4 text-sm space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('backend.kc-tags.store') }}" novalidate
          x-data="kcTagForm()">
        @csrf

        <div class="max-w-xl space-y-5">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-5 space-y-4">

                    {{-- Tổ chức --}}
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tổ chức <span class="text-error">*</span></span>
                        </label>
                        @if($orgLocked)
                            <input type="hidden" name="organization_id" value="{{ $organizations->first()->id }}">
                            <input type="text" value="{{ $organizations->first()->name }}" readonly
                                   class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
                            <p class="mt-1 text-xs text-base-content/40">Xác định từ tài khoản của bạn.</p>
                        @else
                            <select id="ts-organization" name="organization_id"
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

                    <div class="form-control">
                        <label class="label" for="name">
                            <span class="label-text font-medium">Tên tag <span class="text-error">*</span></span>
                        </label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}"
                               @input="onNameInput($event.target.value)"
                               data-req="Tên tag"
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                               placeholder="VD: ISO 9001, Onboarding, HR Policy...">
                        @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label" for="slug">
                            <span class="label-text font-medium">Slug <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs opacity-40">Tự động tạo từ tên</span>
                        </label>
                        <input id="slug" type="text" name="slug" x-model="slug"
                               class="input input-bordered input-sm w-full font-mono @error('slug') input-error @enderror"
                               placeholder="iso-9001">
                        @error('slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label" for="color_hex">
                            <span class="label-text font-medium">Màu hiển thị</span>
                            <span class="label-text-alt text-xs opacity-40">Mã hex, VD: #534AB7</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="color_hex" value="{{ old('color_hex', '#6366f1') }}"
                                   x-model="color"
                                   class="w-9 h-9 rounded cursor-pointer border border-base-300 p-0.5 bg-base-100 shrink-0">
                            <input id="color_hex" type="text" x-model="color" placeholder="#6366f1"
                                   @input="syncColor($event.target.value)"
                                   class="input input-bordered input-sm w-32 font-mono @error('color_hex') input-error @enderror">
                            <span class="badge badge-sm font-medium px-3"
                                  :style="{ backgroundColor: color, color: '#fff' }"
                                  x-text="namePreview || 'Preview'"></span>
                        </div>
                        @error('color_hex')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('backend.kc-tags.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
                <button type="submit" class="btn btn-primary btn-sm">Tạo tag</button>
            </div>

        </div>
    </form>

</div>
@endsection

@push('scripts')
@vite([
    'resources/js/modules/tom-select.js',
    'Modules/KcItem/resources/assets/sass/kc-item.scss',
    'Modules/KcItem/resources/assets/js/kc-item.js',
], 'build/backend')
@endpush
