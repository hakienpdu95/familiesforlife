@extends('layouts.backend')
@section('title', 'Sửa SOP — ' . $sop->code)


@section('content')
<div class="p-6">

    <div class="mb-5">
        <div class="flex items-center gap-2 mb-0.5">
            <span class="font-mono text-sm font-semibold text-primary">{{ $sop->code }}</span>
            <span class="badge badge-sm {{ $sop->status?->badgeClass() }}">{{ $sop->status?->label() }}</span>
        </div>
        <h1 class="text-xl font-bold">Chỉnh sửa quy trình</h1>
    </div>

    @if($sop->status?->value === 'approved')
    <div class="alert alert-warning mb-5">
        <p class="text-sm">SOP này đã được duyệt. Thay đổi sẽ yêu cầu gửi duyệt lại.</p>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-error mb-5">
        <ul class="list-disc pl-4 text-sm space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('backend.sop.update', $sop) }}" novalidate
          data-sop-form>
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

            {{-- ── Nội dung chính ──────────────────────────────────────────── --}}
            <div class="space-y-5">

                {{-- Thông tin cơ bản --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5 space-y-4">
                        <p class="text-sm font-semibold">Thông tin cơ bản</p>

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
                                    <option value="{{ $org->id }}"
                                        {{ old('organization_id', $sop->organization_id) == $org->id ? 'selected' : '' }}>
                                        {{ $org->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-[180px_1fr] gap-4">

                            <div class="form-control">
                                <label class="label" for="code-display">
                                    <span class="label-text font-medium">Mã SOP</span>
                                </label>
                                <input id="code-display" type="text" value="{{ $sop->code }}" readonly
                                       class="input input-bordered input-sm font-mono bg-base-200 cursor-not-allowed opacity-60">
                                <p class="text-xs text-base-content/40 mt-1">Không thể thay đổi sau khi tạo</p>
                            </div>

                            <div class="form-control">
                                <label class="label" for="title">
                                    <span class="label-text font-medium">Tên quy trình <span class="text-error">*</span></span>
                                </label>
                                <input id="title" type="text" name="title"
                                       value="{{ old('title', $sop->title) }}"
                                       data-req="Tên quy trình"
                                       class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                                       placeholder="Nhập tên quy trình...">
                                @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                        </div>

                        <div class="form-control">
                            <label class="label" for="description">
                                <span class="label-text font-medium">Mô tả tổng quan</span>
                            </label>
                            @can('ai_copilot.use')
                            <div class="mb-1.5"
                                 x-data="{
                                     ...aiTask({
                                         agentSlug:   'sop.draft',
                                         variables:   { sop_title: @js($sop->title), department: @js($sop->department?->name ?? ''), type: @js($sop->type?->value ?? '') },
                                         subjectType: 'Modules\\Sop\\Models\\Sop',
                                         subjectId:   {{ $sop->id }},
                                     }),
                                     apply() {
                                         if (!this.output) return;
                                         const jodit = window.Jodit?.instances?.description;
                                         if (jodit) { jodit.value = this.output; }
                                         else { const ta = document.getElementById('description'); if (ta) ta.value = this.output; }
                                     }
                                 }">
                                <button type="button" @click="run()" :disabled="loading"
                                        class="btn btn-xs btn-outline btn-primary gap-1">
                                    <svg x-show="!loading" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    <span x-show="loading" class="loading loading-spinner loading-xs"></span>
                                    <span x-show="!loading">✨ AI gợi ý mô tả</span>
                                </button>
                                <div x-show="error" class="text-xs text-error mt-1" x-text="error"></div>
                                <template x-if="output">
                                    <div class="mt-2 p-3 bg-primary/5 border border-primary/20 rounded-lg text-sm">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-medium text-primary">Gợi ý từ AI</span>
                                            <button type="button" @click="apply()" class="btn btn-xs btn-primary">Dùng gợi ý</button>
                                        </div>
                                        <p x-text="output" class="text-base-content/70 whitespace-pre-wrap max-h-40 overflow-y-auto"></p>
                                    </div>
                                </template>
                            </div>
                            @endcan
                            <textarea id="description" name="description"
                                      class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                                      data-jodit-preset="compact"
                                      rows="3"
                                      placeholder="Mô tả mục tiêu, phạm vi áp dụng của quy trình...">{{ old('description', $sop->description) }}</textarea>
                            @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- Phạm vi & Phân công --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5 space-y-4">
                        <p class="text-sm font-semibold">Phạm vi & Phân công</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <div class="form-control">
                                <label class="label" for="ts-type">
                                    <span class="label-text font-medium">Loại SOP <span class="text-error">*</span></span>
                                </label>
                                <select id="ts-type" name="type"
                                        class="select select-bordered select-sm w-full ts-init @error('type') select-error @enderror">
                                    @foreach($types as $t)
                                    <option value="{{ $t['value'] }}" {{ old('type', $sop->type?->value) === $t['value'] ? 'selected' : '' }}>{{ $t['text'] }}</option>
                                    @endforeach
                                </select>
                                @error('type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label" for="ts-owner">
                                    <span class="label-text font-medium">Người phụ trách <span class="text-error">*</span></span>
                                </label>
                                <select id="ts-owner" name="owner_id"
                                        class="select select-bordered select-sm w-full @error('owner_id') select-error @enderror @if(!$orgLocked) ts-init @endif"
                                        @if($orgLocked)
                                            data-ts-remote-url="{{ route('api.users.options') }}"
                                        @else
                                            data-org-api="{{ route('api.users.options') }}"
                                            data-selected-value="{{ old('owner_id', $sop->owner_id) }}"
                                        @endif
                                        data-ts-placeholder="Chọn người phụ trách...">
                                    @if($orgLocked)
                                        @php $ownerVal = $selectedOwner ?? $sop->owner; @endphp
                                        @if($ownerVal)
                                        <option value="{{ $ownerVal->id }}" selected>{{ $ownerVal->name }}</option>
                                        @endif
                                    @endif
                                </select>
                                @error('owner_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label" for="ts-department">
                                    <span class="label-text font-medium">Phòng ban áp dụng</span>
                                </label>
                                <select id="ts-department" name="department_id"
                                        class="select select-bordered select-sm w-full @if(!$orgLocked) ts-init @endif"
                                        @if($orgLocked)
                                            data-ts-remote-url="{{ route('api.departments.options') }}"
                                        @else
                                            data-org-api="{{ route('api.departments.options') }}"
                                            data-selected-value="{{ old('department_id', $sop->department_id) }}"
                                        @endif
                                        data-ts-placeholder="Tất cả phòng ban...">
                                    @if($orgLocked)
                                        @php $deptVal = $selectedDept ?? $sop->department; @endphp
                                        @if($deptVal)
                                        <option value="{{ $deptVal->id }}" selected>{{ $deptVal->name }}</option>
                                        @endif
                                    @endif
                                </select>
                                @error('department_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label" for="ts-branch">
                                    <span class="label-text font-medium">Chi nhánh áp dụng</span>
                                </label>
                                <select id="ts-branch" name="branch_id"
                                        class="select select-bordered select-sm w-full @if(!$orgLocked) ts-init @endif"
                                        @if($orgLocked)
                                            data-ts-remote-url="{{ route('api.branches.options') }}"
                                        @else
                                            data-org-api="{{ route('api.branches.options') }}"
                                            data-selected-value="{{ old('branch_id', $sop->branch_id) }}"
                                        @endif
                                        data-ts-placeholder="Tất cả chi nhánh...">
                                    @if($orgLocked)
                                        @php $branchVal = $selectedBranch ?? $sop->branch; @endphp
                                        @if($branchVal)
                                        <option value="{{ $branchVal->id }}" selected>{{ $branchVal->name }}</option>
                                        @endif
                                    @endif
                                </select>
                                @error('branch_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Thời hạn hiệu lực --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5 space-y-4">
                        <p class="text-sm font-semibold">Thời hạn hiệu lực</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <div class="form-control">
                                <label class="label" for="fp-effective-date">
                                    <span class="label-text font-medium">Ngày hiệu lực</span>
                                </label>
                                <input id="fp-effective-date" name="effective_date"
                                       value="{{ old('effective_date', $sop->effective_date?->format('Y-m-d')) }}"
                                       class="input input-bordered input-sm w-full fp-init @error('effective_date') input-error @enderror"
                                       placeholder="dd/mm/yyyy" autocomplete="off">
                                @error('effective_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label" for="fp-expired-date">
                                    <span class="label-text font-medium">Ngày hết hạn</span>
                                </label>
                                <input id="fp-expired-date" name="expired_date"
                                       value="{{ old('expired_date', $sop->expired_date?->format('Y-m-d')) }}"
                                       class="input input-bordered input-sm w-full fp-init @error('expired_date') input-error @enderror"
                                       placeholder="dd/mm/yyyy" autocomplete="off">
                                @error('expired_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
            <aside class="xl:sticky xl:top-4 space-y-4">

                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 space-y-4">
                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Trạng thái</p>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="badge badge-sm {{ $sop->status?->badgeClass() }}">{{ $sop->status?->label() }}</span>
                            @if($sop->version > 0)
                            <span class="badge badge-sm badge-outline">v{{ $sop->version }}</span>
                            @endif
                        </div>
                        <div class="text-xs text-base-content/40 space-y-1">
                            <p>Tạo: {{ $sop->created_at->format('d/m/Y') }}</p>
                            <p>Cập nhật: {{ $sop->updated_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex flex-col gap-2 pt-1 border-t border-base-200">
                            <button type="submit" class="btn btn-primary btn-sm w-full">Lưu thay đổi</button>
                            <a href="{{ route('backend.sop.show', $sop) }}"
                               class="btn btn-ghost btn-sm w-full">Hủy</a>
                        </div>
                        <p class="text-center text-xs opacity-30">
                            <span class="text-error">*</span> là trường bắt buộc
                        </p>
                    </div>
                </div>

                @can('delete', $sop)
                <div class="card bg-base-100 shadow-sm border border-error/20">
                    <div class="card-body p-4 space-y-3">
                        <p class="text-xs font-semibold text-error/70 uppercase tracking-wide">Vùng nguy hiểm</p>
                        <form method="POST" action="{{ route('backend.sop.destroy', $sop) }}"
                              onsubmit="return confirm('Bạn có chắc muốn lưu trữ SOP {{ addslashes($sop->code) }}? Thao tác này sẽ ẩn SOP khỏi danh sách.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-error btn-outline btn-sm w-full">
                                Lưu trữ SOP
                            </button>
                        </form>
                    </div>
                </div>
                @endcan

            </aside>

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
    'Modules/Sop/resources/assets/sass/sop.scss',
    'Modules/Sop/resources/assets/js/sop.js',
], 'build/backend')
@endpush
