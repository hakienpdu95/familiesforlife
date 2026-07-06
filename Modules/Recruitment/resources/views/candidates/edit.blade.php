@extends('layouts.backend')

@section('title', 'Chỉnh sửa — ' . $candidate->full_name)


@section('content')
<div
    x-data="{
        tab: 'basic',
        tabFields: { basic: ['organization_id', 'full_name', 'email'], work: [], source: ['source'] },
        errs: {{ Js::from($errors->keys()) }},
        errCount(t) { return (this.tabFields[t] || []).filter(f => this.errs.includes(f)).length },
        init() {
            @if($errors->any())
            for (const t of Object.keys(this.tabFields)) {
                if (this.errCount(t) > 0) { this.tab = t; break; }
            }
            @endif
        },
    }"
    class="p-6"
>
    <div class="mb-5">
        <h1 class="text-xl font-bold">Chỉnh sửa ứng viên</h1>
        <p class="text-sm opacity-60 mt-0.5">{{ $candidate->email }}</p>
    </div>

    <form
        method="POST"
        action="{{ route('backend.recruitment.candidates.update', $candidate) }}"
        data-candidate-form
    >
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

            {{-- ── Main panel ────────────────────────────────────── --}}
            <div class="min-w-0">

                {{-- Tab nav --}}
                <div class="border-b border-base-200 px-6 -mx-6 mb-6">
                    <nav class="flex -mb-px gap-0">
                        <button type="button"
                            class="tab-btn px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'basic'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/60 hover:text-base-content'"
                            @click="tab = 'basic'">
                            Thông tin cơ bản
                            <span x-show="errCount('basic') > 0"
                                  class="badge badge-error badge-xs ml-1"
                                  x-text="errCount('basic')"></span>
                        </button>
                        <button type="button"
                            class="tab-btn px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'work'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/60 hover:text-base-content'"
                            @click="tab = 'work'">
                            Công việc & Kỹ năng
                        </button>
                        <button type="button"
                            class="tab-btn px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'source'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/60 hover:text-base-content'"
                            @click="tab = 'source'">
                            Nguồn & Liên kết
                            <span x-show="errCount('source') > 0"
                                  class="badge badge-error badge-xs ml-1"
                                  x-text="errCount('source')"></span>
                        </button>
                    </nav>
                </div>

                {{-- Tab: Thông tin cơ bản --}}
                <div x-show="tab === 'basic'" data-tab-label="Thông tin cơ bản" class="space-y-4">
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
                                <select id="ts-organization" name="organization_id"
                                        class="select select-bordered select-sm w-full ts-init @error('organization_id') select-error @enderror"
                                        data-ts-placeholder="— Chọn tổ chức —"
                                        data-req="Vui lòng chọn tổ chức">
                                    <option value="">— Chọn tổ chức —</option>
                                    @foreach($organizations as $org)
                                    <option value="{{ $org->id }}" {{ old('organization_id', $candidate->org_id) == $org->id ? 'selected' : '' }}>
                                        {{ $org->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            @endif
                        </div>

                        <div class="form-control">
                            <label class="label" for="full_name">
                                <span class="label-text font-medium">Họ và tên <span class="text-error">*</span></span>
                            </label>
                            <input id="full_name" type="text" name="full_name"
                                   value="{{ old('full_name', $candidate->full_name) }}"
                                   class="input input-bordered input-sm @error('full_name') input-error @enderror"
                                   data-req="Họ và tên">
                            @error('full_name')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label" for="email">
                                <span class="label-text font-medium">Email <span class="text-error">*</span></span>
                            </label>
                            <input id="email" type="email" name="email"
                                   value="{{ old('email', $candidate->email) }}"
                                   class="input input-bordered input-sm @error('email') input-error @enderror"
                                   data-req="Email">
                            @error('email')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label" for="phone">
                                <span class="label-text font-medium">Số điện thoại</span>
                            </label>
                            <input id="phone" type="text" name="phone"
                                   value="{{ old('phone', $candidate->phone) }}"
                                   class="input input-bordered input-sm @error('phone') input-error @enderror"
                                   placeholder="0912 345 678">
                        </div>

                        <div class="form-control">
                            <label class="label" for="ts-gender">
                                <span class="label-text font-medium">Giới tính</span>
                            </label>
                            <select id="ts-gender" name="gender" class="select select-bordered select-sm ts-init">
                                <option value="">— Không xác định —</option>
                                <option value="male"   {{ old('gender', $candidate->gender) === 'male'   ? 'selected' : '' }}>Nam</option>
                                <option value="female" {{ old('gender', $candidate->gender) === 'female' ? 'selected' : '' }}>Nữ</option>
                                <option value="other"  {{ old('gender', $candidate->gender) === 'other'  ? 'selected' : '' }}>Khác</option>
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label" for="fp-date-of-birth">
                                <span class="label-text font-medium">Ngày sinh</span>
                            </label>
                            <input id="fp-date-of-birth" name="date_of_birth"
                                   value="{{ old('date_of_birth', $candidate->date_of_birth?->format('Y-m-d') ?? '') }}"
                                   class="input input-bordered input-sm fp-init"
                                   placeholder="dd/mm/yyyy" autocomplete="off">
                        </div>

                        <div class="form-control">
                            <label class="label" for="years_experience">
                                <span class="label-text font-medium">Số năm kinh nghiệm</span>
                            </label>
                            <input id="years_experience" type="number" name="years_experience"
                                   value="{{ old('years_experience', $candidate->years_experience) }}"
                                   class="input input-bordered input-sm"
                                   min="0" max="50" placeholder="0">
                        </div>
                    </div>
                </div>

                {{-- Tab: Công việc & Kỹ năng --}}
                <div x-show="tab === 'work'" data-tab-label="Công việc & Kỹ năng" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label" for="current_title">
                                <span class="label-text font-medium">Chức danh hiện tại</span>
                            </label>
                            <input id="current_title" type="text" name="current_title"
                                   value="{{ old('current_title', $candidate->current_title) }}"
                                   class="input input-bordered input-sm"
                                   placeholder="Frontend Developer">
                        </div>

                        <div class="form-control">
                            <label class="label" for="current_company">
                                <span class="label-text font-medium">Công ty hiện tại</span>
                            </label>
                            <input id="current_company" type="text" name="current_company"
                                   value="{{ old('current_company', $candidate->current_company) }}"
                                   class="input input-bordered input-sm"
                                   placeholder="FPT Software">
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label" for="skills">
                            <span class="label-text font-medium">Kỹ năng</span>
                        </label>
                        <textarea id="skills" name="skills"
                                  class="textarea textarea-bordered textarea-sm"
                                  rows="3"
                                  placeholder="PHP, Laravel, Vue.js, MySQL (phân cách bởi dấu phẩy)">{{ old('skills', $candidate->skills) }}</textarea>
                        <div class="label">
                            <span class="label-text-alt opacity-50">Phân cách bởi dấu phẩy</span>
                        </div>
                    </div>
                </div>

                {{-- Tab: Nguồn & Liên kết --}}
                <div x-show="tab === 'source'" data-tab-label="Nguồn & Liên kết" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label" for="ts-source">
                                <span class="label-text font-medium">Nguồn ứng viên <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-source" name="source" class="select select-bordered select-sm ts-init" data-req="Nguồn ứng viên">
                                @foreach($sources as $src)
                                <option value="{{ $src['value'] }}" {{ old('source', $candidate->source?->value) === $src['value'] ? 'selected' : '' }}>
                                    {{ $src['text'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('source')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label" for="ts-referred-by">
                                <span class="label-text font-medium">Người giới thiệu</span>
                            </label>
                            <select id="ts-referred-by" name="referred_by" class="select select-bordered select-sm ts-init">
                                <option value="">— Không có —</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('referred_by', $candidate->referred_by) == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label" for="linkedin_url">
                                <span class="label-text font-medium">LinkedIn</span>
                            </label>
                            <input id="linkedin_url" type="url" name="linkedin_url"
                                   value="{{ old('linkedin_url', $candidate->linkedin_url) }}"
                                   class="input input-bordered input-sm"
                                   placeholder="https://linkedin.com/in/...">
                        </div>

                        <div class="form-control">
                            <label class="label" for="portfolio_url">
                                <span class="label-text font-medium">Portfolio / Website</span>
                            </label>
                            <input id="portfolio_url" type="url" name="portfolio_url"
                                   value="{{ old('portfolio_url', $candidate->portfolio_url) }}"
                                   class="input input-bordered input-sm"
                                   placeholder="https://...">
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── Sidebar ───────────────────────────────────────── --}}
            <aside class="xl:sticky xl:top-4 space-y-4">
                <div class="card bg-base-100 border border-base-200 shadow-sm">
                    <div class="card-body p-4 space-y-4">

                        {{-- Status badge (read-only) --}}
                        <div>
                            <p class="text-xs text-base-content/50 uppercase tracking-wide font-medium mb-1">Trạng thái</p>
                            @php
                                $statusLabel = $candidate->status?->label() ?? 'Active';
                                $statusClass = match($candidate->status?->value) {
                                    'active'      => 'badge-success',
                                    'hired'       => 'badge-info',
                                    'blacklisted' => 'badge-error',
                                    'inactive'    => 'badge-ghost',
                                    default       => 'badge-ghost',
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>

                        {{-- Timestamps --}}
                        <div class="text-xs text-base-content/40 space-y-1">
                            <p>Tạo lúc: {{ $candidate->created_at->format('d/m/Y') }}</p>
                            <p>Cập nhật: {{ $candidate->updated_at->diffForHumans() }}</p>
                        </div>

                        <div class="flex flex-col gap-2 pt-1 border-t border-base-200">
                            <button type="submit" class="btn btn-primary btn-sm w-full">Lưu thay đổi</button>
                            <a href="{{ route('backend.recruitment.candidates.show', $candidate) }}"
                               class="btn btn-ghost btn-sm w-full">Hủy</a>
                        </div>
                    </div>
                </div>
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
    'Modules/Recruitment/resources/assets/sass/recruitment.scss',
    'Modules/Recruitment/resources/assets/js/recruitment.js',
], 'build/backend')
@endpush
