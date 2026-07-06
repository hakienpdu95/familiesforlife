@extends('layouts.backend')
@section('title', $jobPost->title)


@section('content')
<div x-data="{ activeTab: 'detail' }">

    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <h1 class="text-2xl font-bold text-base-content">{{ $jobPost->title }}</h1>
                <span class="badge badge-sm badge-soft {{ $jobPost->status->badgeClass() }}">{{ $jobPost->status->label() }}</span>
            </div>
            <p class="text-sm text-base-content/50">
                {{ $jobPost->code }}
                @if($jobPost->department)· {{ $jobPost->department->name }}@endif
                @if($jobPost->owner)· {{ $jobPost->owner->name }}@endif
            </p>
        </div>

        <div class="flex flex-wrap gap-2 items-center">

            {{-- Sync-marketplace badge --}}
            @if($jobPost->mkt_sync_status?->value === 'out_of_sync')
            @can('publish', $jobPost)
            <form method="POST" action="{{ route('backend.job-posts.sync-marketplace', $jobPost) }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm gap-1 animate-pulse">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Sync Marketplace
                </button>
            </form>
            @endcan
            @endif

            @can('update', $jobPost)
            <a href="{{ route('backend.job-posts.edit', $jobPost) }}" class="btn btn-ghost btn-sm gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                Sửa
            </a>
            @endcan

            {{-- Submit review (draft only) --}}
            @can('update', $jobPost)
            @if($jobPost->status->value === 'draft')
            <form method="POST" action="{{ route('backend.job-posts.submit-review', $jobPost) }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-info btn-sm gap-1">
                    Gửi duyệt
                </button>
            </form>
            @endif
            @endcan

            @can('publish', $jobPost)
            @if(in_array($jobPost->status->value, ['draft', 'pending_review', 'paused']))
            <form method="POST" action="{{ route('backend.job-posts.publish', $jobPost) }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-success btn-sm gap-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Đăng tuyển
                </button>
            </form>
            @endif
            @endcan

            @can('close', $jobPost)
            @if($jobPost->status->value === 'published')
            {{-- Pause --}}
            <form method="POST" action="{{ route('backend.job-posts.pause', $jobPost) }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm gap-1"
                        onclick="return confirm('Tạm dừng tin tuyển dụng này?')">
                    Tạm dừng
                </button>
            </form>
            {{-- Close --}}
            <form method="POST" action="{{ route('backend.job-posts.close', $jobPost) }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm gap-1"
                        onclick="return confirm('Đóng tin tuyển dụng này?')">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    Đóng tin
                </button>
            </form>
            @endif

            @if($jobPost->status->value === 'closed')
            <form method="POST" action="{{ route('backend.job-posts.archive', $jobPost) }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm gap-1"
                        onclick="return confirm('Lưu trữ tin tuyển dụng này?')">
                    Lưu trữ
                </button>
            </form>
            @endif
            @endcan

            {{-- Duplicate --}}
            @can('create', \Modules\JobPosting\Models\JpJobPost::class)
            <form method="POST" action="{{ route('backend.job-posts.duplicate', $jobPost) }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm gap-1">
                    Nhân bản
                </button>
            </form>
            @endcan

            @can('delete', $jobPost)
            <form method="POST" action="{{ route('backend.job-posts.destroy', $jobPost) }}" class="inline"
                  onsubmit="return confirm('Xóa tin tuyển dụng này?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-sm text-error gap-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Xóa
                </button>
            </form>
            @endcan

        </div>
    </div>

    {{-- Stat row --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Số lượng tuyển</div>
            <div class="stat-value text-xl">{{ $jobPost->headcount }}</div>
            <div class="stat-desc text-xs">Đã tuyển: {{ $jobPost->hired_count }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Lượt xem</div>
            <div class="stat-value text-xl">{{ number_format($jobPost->view_count) }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Ứng viên</div>
            <div class="stat-value text-xl">{{ number_format($jobPost->application_count) }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Chia sẻ</div>
            <div class="stat-value text-xl">{{ number_format($jobPost->share_count) }}</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="tabs tabs-border mb-4">
        <button class="tab" :class="activeTab === 'detail'    && 'tab-active'" @click="activeTab = 'detail'">Chi tiết</button>
        <button class="tab" :class="activeTab === 'skills'    && 'tab-active'" @click="activeTab = 'skills'">Kỹ năng</button>
        <button class="tab" :class="activeTab === 'benefits'  && 'tab-active'" @click="activeTab = 'benefits'">Phúc lợi</button>
        <button class="tab" :class="activeTab === 'questions' && 'tab-active'" @click="activeTab = 'questions'">Câu hỏi sàng lọc</button>
        <button class="tab" :class="activeTab === 'analytics' && 'tab-active'" @click="activeTab = 'analytics'">Analytics</button>
        <button class="tab" :class="activeTab === 'history'   && 'tab-active'" @click="activeTab = 'history'">Lịch sử</button>
    </div>

    {{-- ─────────────────────────────────────────────────────────── --}}
    {{-- Tab: Detail                                                 --}}
    {{-- ─────────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'detail'" x-transition>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <div class="lg:col-span-2 space-y-4">

                @if($jobPost->summary)
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5">
                        <h2 class="font-semibold text-base mb-2">Mô tả ngắn</h2>
                        <p class="text-sm text-base-content/80 whitespace-pre-wrap">{{ $jobPost->summary }}</p>
                    </div>
                </div>
                @endif

                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5">
                        <h2 class="font-semibold text-base mb-2">Mô tả công việc</h2>
                        <div class="prose prose-sm max-w-none text-base-content/80">
                            {!! nl2br(e($jobPost->description)) !!}
                        </div>
                    </div>
                </div>

                @if($jobPost->responsibilities)
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5">
                        <h2 class="font-semibold text-base mb-2">Trách nhiệm</h2>
                        <div class="prose prose-sm max-w-none text-base-content/80">
                            {!! nl2br(e($jobPost->responsibilities)) !!}
                        </div>
                    </div>
                </div>
                @endif

                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5">
                        <h2 class="font-semibold text-base mb-2">Yêu cầu ứng viên</h2>
                        <div class="prose prose-sm max-w-none text-base-content/80">
                            {!! nl2br(e($jobPost->requirements)) !!}
                        </div>
                    </div>
                </div>

                @if($jobPost->nice_to_have)
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5">
                        <h2 class="font-semibold text-base mb-2">Yêu cầu phụ</h2>
                        <div class="prose prose-sm max-w-none text-base-content/80">
                            {!! nl2br(e($jobPost->nice_to_have)) !!}
                        </div>
                    </div>
                </div>
                @endif

                @if($jobPost->what_you_will_learn)
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5">
                        <h2 class="font-semibold text-base mb-2">Bạn sẽ học được gì</h2>
                        <div class="prose prose-sm max-w-none text-base-content/80">
                            {!! nl2br(e($jobPost->what_you_will_learn)) !!}
                        </div>
                    </div>
                </div>
                @endif

            </div>

            <div class="space-y-4">

                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5">
                        <h2 class="font-semibold text-base mb-3">Thông tin chung</h2>
                        <dl class="space-y-2 text-sm">
                            <div class="flex gap-2 justify-between">
                                <dt class="text-base-content/50">Phòng ban</dt>
                                <dd class="font-medium text-right">{{ $jobPost->department?->name ?? '—' }}</dd>
                            </div>
                            <div class="flex gap-2 justify-between">
                                <dt class="text-base-content/50">Chức danh</dt>
                                <dd class="font-medium text-right">{{ $jobPost->jobTitle?->name ?? '—' }}</dd>
                            </div>
                            <div class="flex gap-2 justify-between">
                                <dt class="text-base-content/50">Loại hình</dt>
                                <dd class="font-medium text-right">{{ $jobPost->employment_type?->label() ?? '—' }}</dd>
                            </div>
                            <div class="flex gap-2 justify-between">
                                <dt class="text-base-content/50">Hình thức</dt>
                                <dd class="font-medium text-right">{{ $jobPost->work_arrangement?->label() ?? '—' }}</dd>
                            </div>
                            <div class="flex gap-2 justify-between">
                                <dt class="text-base-content/50">Cấp độ</dt>
                                <dd class="font-medium text-right">{{ $jobPost->experience_level?->label() ?? '—' }}</dd>
                            </div>
                            <div class="flex gap-2 justify-between">
                                <dt class="text-base-content/50">Ngành nghề</dt>
                                <dd class="font-medium text-right">{{ $jobPost->industry?->label() ?? '—' }}</dd>
                            </div>
                            <div class="flex gap-2 justify-between">
                                <dt class="text-base-content/50">Người phụ trách</dt>
                                <dd class="font-medium text-right">{{ $jobPost->owner?->name ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5">
                        <h2 class="font-semibold text-base mb-3">Lương</h2>
                        @if($jobPost->salary_is_negotiable)
                            <p class="text-sm font-medium">Thỏa thuận</p>
                        @elseif($jobPost->salary_min || $jobPost->salary_max)
                            <p class="text-sm font-medium">
                                @if($jobPost->salary_min && $jobPost->salary_max)
                                    {{ number_format($jobPost->salary_min) }} – {{ number_format($jobPost->salary_max) }} {{ $jobPost->salary_currency }}
                                @elseif($jobPost->salary_min)
                                    Từ {{ number_format($jobPost->salary_min) }} {{ $jobPost->salary_currency }}
                                @else
                                    Đến {{ number_format($jobPost->salary_max) }} {{ $jobPost->salary_currency }}
                                @endif
                                / {{ $jobPost->salary_type?->label() }}
                            </p>
                        @else
                            <p class="text-sm text-base-content/40">Chưa cấu hình</p>
                        @endif
                        @if($jobPost->salary_note)
                        <p class="text-xs text-base-content/50 mt-1">{{ $jobPost->salary_note }}</p>
                        @endif
                    </div>
                </div>

                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5">
                        <h2 class="font-semibold text-base mb-3">Địa điểm</h2>
                        <dl class="space-y-2 text-sm">
                            @if($jobPost->city)
                            <div class="flex gap-2 justify-between">
                                <dt class="text-base-content/50">Thành phố</dt>
                                <dd class="font-medium">{{ $jobPost->city }}</dd>
                            </div>
                            @endif
                            <div class="flex gap-2 justify-between">
                                <dt class="text-base-content/50">Quốc gia</dt>
                                <dd class="font-medium">{{ $jobPost->country }}</dd>
                            </div>
                            <div class="flex gap-2 justify-between">
                                <dt class="text-base-content/50">Remote</dt>
                                <dd>
                                    @if($jobPost->is_remote_allowed)
                                        <span class="badge badge-success badge-xs">Cho phép</span>
                                    @else
                                        <span class="badge badge-ghost badge-xs">Không</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5">
                        <h2 class="font-semibold text-base mb-3">Thời gian</h2>
                        <dl class="space-y-2 text-sm">
                            <div class="flex gap-2 justify-between">
                                <dt class="text-base-content/50">Tạo lúc</dt>
                                <dd class="font-medium">{{ $jobPost->created_at?->format('d/m/Y H:i') }}</dd>
                            </div>
                            @if($jobPost->published_at)
                            <div class="flex gap-2 justify-between">
                                <dt class="text-base-content/50">Đăng lúc</dt>
                                <dd class="font-medium">{{ $jobPost->published_at->format('d/m/Y H:i') }}</dd>
                            </div>
                            @endif
                            @if($jobPost->expire_at)
                            <div class="flex gap-2 justify-between">
                                <dt class="text-base-content/50">Hạn nộp</dt>
                                <dd class="font-medium {{ $jobPost->expire_at->isPast() ? 'text-error' : '' }}">
                                    {{ $jobPost->expire_at->format('d/m/Y H:i') }}
                                </dd>
                            </div>
                            @endif
                            @if($jobPost->closed_at)
                            <div class="flex gap-2 justify-between">
                                <dt class="text-base-content/50">Đóng lúc</dt>
                                <dd class="font-medium">{{ $jobPost->closed_at->format('d/m/Y H:i') }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5">
                        <h2 class="font-semibold text-base mb-3">Phân phối kênh</h2>
                        <dl class="space-y-2 text-sm">
                            <div class="flex gap-2 justify-between">
                                <dt class="text-base-content/50">Career page</dt>
                                <dd>
                                    @if($jobPost->publish_to_career_page)
                                        <span class="badge badge-success badge-xs">Bật</span>
                                    @else
                                        <span class="badge badge-ghost badge-xs">Tắt</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex gap-2 justify-between">
                                <dt class="text-base-content/50">Marketplace</dt>
                                <dd>
                                    @if($jobPost->publish_to_marketplace)
                                        <span class="badge badge-success badge-xs">Bật</span>
                                        @if($jobPost->mkt_sync_status)
                                        <span class="badge badge-xs ml-1">{{ $jobPost->mkt_sync_status->label() }}</span>
                                        @endif
                                    @else
                                        <span class="badge badge-ghost badge-xs">Tắt</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ─────────────────────────────────────────────────────────── --}}
    {{-- Tab: Skills                                                 --}}
    {{-- ─────────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'skills'" x-transition
         x-data="skillsManager({{ $jobPost->id }})"
         x-init="load()">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">

                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-base">
                        Kỹ năng yêu cầu
                        <span class="text-sm font-normal text-base-content/50" x-text="`(${skills.length}/20)`"></span>
                    </h2>
                </div>

                {{-- Add form --}}
                @can('update', $jobPost)
                <div class="bg-base-200/50 rounded-xl p-4 mb-5 border border-base-200">
                    <p class="text-xs font-medium text-base-content/60 mb-3">Thêm kỹ năng</p>
                    <div class="flex flex-wrap gap-2 items-end">

                        {{-- Skill name autocomplete --}}
                        <div class="relative flex-1 min-w-48">
                            <label class="text-xs text-base-content/50 mb-1 block">Tên kỹ năng <span class="text-error">*</span></label>
                            <input type="text" class="input input-sm input-bordered w-full"
                                   placeholder="PHP, Laravel, React..."
                                   x-model="form.skillName"
                                   @input="searchSkills()"
                                   @keydown.escape="showDropdown = false"
                                   @keydown.enter.prevent="suggestions.length ? selectSuggestion(suggestions[0]) : null"
                                   autocomplete="off">
                            <div x-show="showDropdown" x-transition
                                 class="absolute z-50 top-full left-0 right-0 mt-1 bg-base-100 border border-base-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"
                                 @click.outside="showDropdown = false">
                                <template x-for="s in suggestions" :key="s.id">
                                    <button type="button"
                                            class="w-full text-left px-3 py-2 text-sm hover:bg-base-200 flex items-center justify-between"
                                            @click="selectSuggestion(s)">
                                        <span x-text="s.name"></span>
                                        <span class="text-xs text-base-content/40" x-text="s.category"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Requirement level --}}
                        <div class="w-36">
                            <label class="text-xs text-base-content/50 mb-1 block">Mức yêu cầu</label>
                            <select class="select select-sm select-bordered w-full" x-model="form.requirementLevel">
                                <option value="required">Bắt buộc</option>
                                <option value="preferred">Ưu tiên</option>
                                <option value="nice_to_have">Có thì tốt</option>
                            </select>
                        </div>

                        {{-- Proficiency --}}
                        <div class="w-36">
                            <label class="text-xs text-base-content/50 mb-1 block">Trình độ</label>
                            <select class="select select-sm select-bordered w-full" x-model="form.proficiency">
                                <option value="">— Không chọn</option>
                                <option value="beginner">Cơ bản</option>
                                <option value="intermediate">Trung cấp</option>
                                <option value="advanced">Nâng cao</option>
                                <option value="expert">Chuyên gia</option>
                            </select>
                        </div>

                        {{-- Min years --}}
                        <div class="w-28">
                            <label class="text-xs text-base-content/50 mb-1 block">Tối thiểu (năm)</label>
                            <input type="number" class="input input-sm input-bordered w-full"
                                   placeholder="0" min="0" max="50"
                                   x-model.number="form.minYears">
                        </div>

                        <button type="button" class="btn btn-primary btn-sm self-end"
                                :disabled="!form.skillName.trim() || saving || skills.length >= 20"
                                @click="addSkill()">
                            <span x-show="!saving">+ Thêm</span>
                            <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                        </button>
                    </div>
                    <p x-show="error" x-text="error" class="text-error text-xs mt-2"></p>
                </div>
                @endcan

                {{-- Loading --}}
                <div x-show="loading" class="flex justify-center py-8">
                    <span class="loading loading-spinner loading-md text-base-content/30"></span>
                </div>

                {{-- Empty state --}}
                <div x-show="!loading && skills.length === 0" class="text-center py-8 text-base-content/40 text-sm">
                    Chưa có kỹ năng nào. Thêm kỹ năng yêu cầu để ứng viên biết cần chuẩn bị gì.
                </div>

                {{-- Skill groups --}}
                <div x-show="!loading && skills.length > 0" class="space-y-4">

                    {{-- Required --}}
                    <template x-if="required.length">
                        <div>
                            <p class="text-xs font-semibold text-error uppercase tracking-wide mb-2">Bắt buộc</p>
                            <div class="space-y-1.5">
                                <template x-for="s in required" :key="s.id">
                                    <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-base-200/40 group">
                                        <div class="flex-1 flex flex-wrap items-center gap-2">
                                            <span class="font-medium text-sm" x-text="s.skill_name"></span>
                                            <span x-show="s.skill_category" class="text-xs text-base-content/40" x-text="s.skill_category"></span>
                                            <span x-show="s.proficiency_label" class="badge badge-xs badge-soft badge-neutral" x-text="s.proficiency_label"></span>
                                            <span x-show="s.min_years" class="text-xs text-base-content/50" x-text="`≥ ${s.min_years} năm`"></span>
                                        </div>
                                        @can('update', $jobPost)
                                        <button type="button" class="btn btn-ghost btn-xs text-error opacity-0 group-hover:opacity-100 transition-opacity"
                                                @click="removeSkill(s.id)">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        @endcan
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Preferred --}}
                    <template x-if="preferred.length">
                        <div>
                            <p class="text-xs font-semibold text-warning uppercase tracking-wide mb-2">Ưu tiên</p>
                            <div class="space-y-1.5">
                                <template x-for="s in preferred" :key="s.id">
                                    <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-base-200/40 group">
                                        <div class="flex-1 flex flex-wrap items-center gap-2">
                                            <span class="font-medium text-sm" x-text="s.skill_name"></span>
                                            <span x-show="s.skill_category" class="text-xs text-base-content/40" x-text="s.skill_category"></span>
                                            <span x-show="s.proficiency_label" class="badge badge-xs badge-soft badge-neutral" x-text="s.proficiency_label"></span>
                                            <span x-show="s.min_years" class="text-xs text-base-content/50" x-text="`≥ ${s.min_years} năm`"></span>
                                        </div>
                                        @can('update', $jobPost)
                                        <button type="button" class="btn btn-ghost btn-xs text-error opacity-0 group-hover:opacity-100 transition-opacity"
                                                @click="removeSkill(s.id)">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        @endcan
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Nice to have --}}
                    <template x-if="niceToHave.length">
                        <div>
                            <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-2">Có thì tốt</p>
                            <div class="space-y-1.5">
                                <template x-for="s in niceToHave" :key="s.id">
                                    <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-base-200/40 group">
                                        <div class="flex-1 flex flex-wrap items-center gap-2">
                                            <span class="font-medium text-sm" x-text="s.skill_name"></span>
                                            <span x-show="s.skill_category" class="text-xs text-base-content/40" x-text="s.skill_category"></span>
                                            <span x-show="s.proficiency_label" class="badge badge-xs badge-soft badge-neutral" x-text="s.proficiency_label"></span>
                                            <span x-show="s.min_years" class="text-xs text-base-content/50" x-text="`≥ ${s.min_years} năm`"></span>
                                        </div>
                                        @can('update', $jobPost)
                                        <button type="button" class="btn btn-ghost btn-xs text-error opacity-0 group-hover:opacity-100 transition-opacity"
                                                @click="removeSkill(s.id)">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        @endcan
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                </div>

            </div>
        </div>
    </div>

    {{-- ─────────────────────────────────────────────────────────── --}}
    {{-- Tab: Benefits                                               --}}
    {{-- ─────────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'benefits'" x-transition
         x-data="benefitsManager({{ $jobPost->id }})"
         x-init="load()">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">

                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-base">
                        Phúc lợi
                        <span class="text-sm font-normal text-base-content/50" x-text="`(${benefits.length})`"></span>
                    </h2>
                    @can('update', $jobPost)
                    <button type="button" class="btn btn-sm btn-outline gap-1" @click="showCatalog = !showCatalog">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Thêm từ danh mục
                    </button>
                    @endcan
                </div>

                {{-- Catalog picker --}}
                @can('update', $jobPost)
                <div x-show="showCatalog" x-transition class="mb-5 bg-base-200/50 rounded-xl p-4 border border-base-200">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-medium text-base-content/60">Chọn từ danh mục</p>
                        <button type="button" class="btn btn-ghost btn-xs" @click="showCatalog = false">Đóng</button>
                    </div>

                    {{-- Category groups --}}
                    <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                        <template x-for="cat in catalogCategories" :key="cat">
                            <div>
                                <p class="text-xs text-base-content/40 uppercase tracking-wide mb-1.5" x-text="catLabel(cat)"></p>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="b in catalogByCategory(cat)" :key="b.id">
                                        <button type="button"
                                                class="btn btn-xs gap-1 transition-all"
                                                :class="isAdded(b.id) ? 'btn-success' : 'btn-outline'"
                                                :disabled="isAdded(b.id)"
                                                @click="addFromCatalog(b)">
                                            <i x-show="b.icon" :class="`ti ${b.icon}`" class="text-sm"></i>
                                            <span x-text="b.name"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Custom benefit --}}
                    <div class="divider text-xs text-base-content/40 my-3">hoặc tự nhập</div>
                    <div class="flex gap-2">
                        <input type="text" class="input input-sm input-bordered flex-1"
                               placeholder="Tên phúc lợi tùy chỉnh"
                               x-model="customName"
                               @keydown.enter.prevent="addCustom()">
                        <button type="button" class="btn btn-primary btn-sm"
                                :disabled="!customName.trim() || saving"
                                @click="addCustom()">
                            <span x-show="!saving">Thêm</span>
                            <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                        </button>
                    </div>
                </div>
                @endcan

                {{-- Loading --}}
                <div x-show="loading" class="flex justify-center py-8">
                    <span class="loading loading-spinner loading-md text-base-content/30"></span>
                </div>

                {{-- Empty --}}
                <div x-show="!loading && benefits.length === 0" class="text-center py-8 text-base-content/40 text-sm">
                    Chưa có phúc lợi nào. Thêm phúc lợi để thu hút ứng viên tốt hơn.
                </div>

                {{-- Benefits grid --}}
                <div x-show="!loading && benefits.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    <template x-for="b in benefits" :key="b.id">
                        <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg border border-base-200 bg-base-100 group hover:border-base-300 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                <i x-show="b.icon" :class="`ti ${b.icon} text-primary text-base`"></i>
                                <svg x-show="!b.icon" class="w-4 h-4 text-primary/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate" x-text="b.benefit_name"></p>
                                <p x-show="b.description" class="text-xs text-base-content/50 truncate" x-text="b.description"></p>
                            </div>
                            @can('update', $jobPost)
                            <button type="button" class="btn btn-ghost btn-xs text-error opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0"
                                    @click="removeBenefit(b.id)">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            @endcan
                        </div>
                    </template>
                </div>

            </div>
        </div>
    </div>

    {{-- ─────────────────────────────────────────────────────────── --}}
    {{-- Tab: Screening Questions                                    --}}
    {{-- ─────────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'questions'" x-transition
         x-data="questionsManager({{ $jobPost->id }})"
         x-init="load()">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">

                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-base">
                        Câu hỏi sàng lọc
                        <span class="text-sm font-normal text-base-content/50" x-text="`(${questions.length}/10)`"></span>
                    </h2>
                    @can('update', $jobPost)
                    <button type="button" class="btn btn-sm btn-outline gap-1"
                            :disabled="questions.length >= 10"
                            @click="openAddForm()">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Thêm câu hỏi
                    </button>
                    @endcan
                </div>

                {{-- Add / Edit form --}}
                @can('update', $jobPost)
                <div x-show="showForm" x-transition class="mb-5 bg-base-200/50 rounded-xl p-4 border border-base-200">
                    <p class="text-xs font-medium text-base-content/60 mb-3" x-text="editingId ? 'Chỉnh sửa câu hỏi' : 'Thêm câu hỏi mới'"></p>

                    <div class="space-y-3">

                        <div>
                            <label class="text-xs text-base-content/50 mb-1 block">Nội dung câu hỏi <span class="text-error">*</span></label>
                            <textarea class="textarea textarea-bordered textarea-sm w-full" rows="2"
                                      placeholder="Bạn có thể đi làm toàn thời gian không?"
                                      x-model="form.questionText" maxlength="500"></textarea>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <div class="w-44">
                                <label class="text-xs text-base-content/50 mb-1 block">Loại câu hỏi</label>
                                <select class="select select-sm select-bordered w-full" x-model="form.questionType"
                                        @change="form.choices = []">
                                    <option value="yes_no">Có/Không</option>
                                    <option value="short_text">Trả lời ngắn</option>
                                    <option value="long_text">Trả lời dài</option>
                                    <option value="number">Số</option>
                                    <option value="single_choice">Chọn một</option>
                                    <option value="multiple_choice">Chọn nhiều</option>
                                    <option value="file_upload">Upload file</option>
                                </select>
                            </div>

                            <div class="flex items-end gap-3 pb-0.5">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" class="checkbox checkbox-sm checkbox-primary"
                                           x-model="form.isRequired">
                                    <span class="text-sm">Bắt buộc</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" class="checkbox checkbox-sm checkbox-error"
                                           x-model="form.isDisqualifying">
                                    <span class="text-sm">Disqualifying</span>
                                </label>
                            </div>
                        </div>

                        {{-- Disqualify answer (for yes_no) --}}
                        <div x-show="form.isDisqualifying && form.questionType === 'yes_no'">
                            <label class="text-xs text-base-content/50 mb-1 block">Câu trả lời gây loại</label>
                            <select class="select select-sm select-bordered w-40" x-model="form.disqualifyIfAnswer">
                                <option value="no">Không (No)</option>
                                <option value="yes">Có (Yes)</option>
                            </select>
                        </div>

                        {{-- Choices for single/multiple_choice --}}
                        <div x-show="form.questionType === 'single_choice' || form.questionType === 'multiple_choice'">
                            <label class="text-xs text-base-content/50 mb-2 block">Lựa chọn</label>
                            <div class="space-y-2">
                                <template x-for="(choice, idx) in form.choices" :key="idx">
                                    <div class="flex items-center gap-2">
                                        <input type="text" class="input input-xs input-bordered flex-1"
                                               :placeholder="`Lựa chọn ${idx + 1}`"
                                               x-model="choice.choice_text">
                                        <label class="flex items-center gap-1 text-xs cursor-pointer">
                                            <input type="checkbox" class="checkbox checkbox-xs checkbox-error"
                                                   x-model="choice.is_disqualifying">
                                            <span class="text-base-content/50">DQ</span>
                                        </label>
                                        <button type="button" class="btn btn-ghost btn-xs text-error"
                                                @click="form.choices.splice(idx, 1)">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </template>
                                <button type="button" class="btn btn-ghost btn-xs gap-1"
                                        x-show="form.choices.length < 10"
                                        @click="form.choices.push({ choice_text: '', is_disqualifying: false })">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Thêm lựa chọn
                                </button>
                            </div>
                        </div>

                    </div>

                    <p x-show="formError" x-text="formError" class="text-error text-xs mt-2"></p>

                    <div class="flex gap-2 mt-4">
                        <button type="button" class="btn btn-primary btn-sm"
                                :disabled="!form.questionText.trim() || saving"
                                @click="saveQuestion()">
                            <span x-show="!saving" x-text="editingId ? 'Cập nhật' : 'Lưu câu hỏi'"></span>
                            <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm" @click="closeForm()">Hủy</button>
                    </div>
                </div>
                @endcan

                {{-- Loading --}}
                <div x-show="loading" class="flex justify-center py-8">
                    <span class="loading loading-spinner loading-md text-base-content/30"></span>
                </div>

                {{-- Empty --}}
                <div x-show="!loading && questions.length === 0" class="text-center py-8 text-base-content/40 text-sm">
                    Chưa có câu hỏi sàng lọc. Thêm câu hỏi để lọc ứng viên phù hợp hơn.
                </div>

                {{-- Questions list --}}
                <div x-show="!loading && questions.length > 0" class="space-y-3">
                    <template x-for="(q, idx) in questions" :key="q.id">
                        <div class="border border-base-200 rounded-xl p-4 group hover:border-base-300 transition-colors">
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary/10 text-primary text-xs flex items-center justify-center font-semibold mt-0.5"
                                      x-text="idx + 1"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium" x-text="q.question_text"></p>
                                    <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                        <span class="badge badge-xs badge-soft badge-neutral" x-text="q.question_type_label"></span>
                                        <span x-show="q.is_required" class="badge badge-xs badge-soft badge-primary">Bắt buộc</span>
                                        <span x-show="q.is_disqualifying" class="badge badge-xs badge-soft badge-error">Disqualifying</span>
                                    </div>
                                    {{-- Choices --}}
                                    <div x-show="q.choices && q.choices.length > 0" class="mt-2 space-y-1">
                                        <template x-for="c in q.choices" :key="c.id">
                                            <div class="flex items-center gap-1.5 text-xs text-base-content/60">
                                                <span class="w-1.5 h-1.5 rounded-full bg-base-content/30 flex-shrink-0"></span>
                                                <span x-text="c.choice_text"></span>
                                                <span x-show="c.is_disqualifying" class="badge badge-xs badge-error">DQ</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                @can('update', $jobPost)
                                <div class="flex gap-1 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button type="button" class="btn btn-ghost btn-xs" title="Di chuyển lên"
                                            :disabled="idx === 0"
                                            @click="moveUp(idx)">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    </button>
                                    <button type="button" class="btn btn-ghost btn-xs" title="Di chuyển xuống"
                                            :disabled="idx === questions.length - 1"
                                            @click="moveDown(idx)">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <button type="button" class="btn btn-ghost btn-xs" title="Chỉnh sửa"
                                            @click="editQuestion(q)">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    <button type="button" class="btn btn-ghost btn-xs text-error" title="Xóa"
                                            @click="removeQuestion(q.id)">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                @endcan
                            </div>
                        </div>
                    </template>
                </div>

            </div>
        </div>
    </div>

    {{-- ─────────────────────────────────────────────────────────── --}}
    {{-- Tab: Analytics                                              --}}
    {{-- ─────────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'analytics'" x-transition
         x-data="analyticsManager({{ $jobPost->id }})"
         x-init="load()">

        {{-- Loading --}}
        <div x-show="loading" class="flex justify-center py-16">
            <span class="loading loading-spinner loading-md text-base-content/30"></span>
        </div>

        <div x-show="!loading">

            {{-- Summary stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
                    <div class="stat-title text-xs">Lượt xem (30 ngày)</div>
                    <div class="stat-value text-xl" x-text="totals.views?.toLocaleString() ?? '—'"></div>
                    <div class="stat-desc text-xs" x-text="`Duy nhất: ${totals.unique_views?.toLocaleString() ?? 0}`"></div>
                </div>
                <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
                    <div class="stat-title text-xs">Ứng tuyển (30 ngày)</div>
                    <div class="stat-value text-xl" x-text="totals.applies?.toLocaleString() ?? '—'"></div>
                </div>
                <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
                    <div class="stat-title text-xs">Tỷ lệ apply</div>
                    <div class="stat-value text-xl" x-text="`${totals.conversion_pct ?? 0}%`"></div>
                    <div class="stat-desc text-xs">views → applies</div>
                </div>
                <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
                    <div class="stat-title text-xs">Tổng ứng viên</div>
                    <div class="stat-value text-xl">{{ number_format($jobPost->application_count) }}</div>
                    <div class="stat-desc text-xs">Tổng từ đầu</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                {{-- Source breakdown --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5">
                        <h2 class="font-semibold text-base mb-4">Phân tích theo kênh (30 ngày)</h2>
                        <div x-show="bySource.length === 0" class="text-sm text-base-content/40 text-center py-6">
                            Chưa có dữ liệu
                        </div>
                        <div x-show="bySource.length > 0" class="overflow-x-auto">
                            <table class="table table-sm w-full">
                                <thead>
                                    <tr class="text-xs text-base-content/50 uppercase">
                                        <th>Kênh</th>
                                        <th class="text-right">Lượt xem</th>
                                        <th class="text-right">Ứng tuyển</th>
                                        <th class="text-right">Tỷ lệ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="row in bySource" :key="row.source">
                                        <tr class="hover">
                                            <td>
                                                <span class="badge badge-xs badge-soft badge-neutral capitalize" x-text="sourceLabel(row.source)"></span>
                                            </td>
                                            <td class="text-right text-sm" x-text="row.views.toLocaleString()"></td>
                                            <td class="text-right text-sm" x-text="row.applies.toLocaleString()"></td>
                                            <td class="text-right text-sm" x-text="`${row.conversion_pct}%`"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Daily trend --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5">
                        <h2 class="font-semibold text-base mb-4">Xu hướng theo ngày (30 ngày)</h2>
                        <div x-show="daily.length === 0" class="text-sm text-base-content/40 text-center py-6">
                            Chưa có dữ liệu
                        </div>
                        <div x-show="daily.length > 0" class="overflow-x-auto max-h-72 overflow-y-auto">
                            <table class="table table-sm w-full">
                                <thead class="sticky top-0 bg-base-100">
                                    <tr class="text-xs text-base-content/50 uppercase">
                                        <th>Ngày</th>
                                        <th class="text-right">Lượt xem</th>
                                        <th class="text-right">Ứng tuyển</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="row in daily" :key="row.date">
                                        <tr class="hover">
                                            <td class="text-xs whitespace-nowrap text-base-content/70" x-text="row.date"></td>
                                            <td class="text-right text-sm" x-text="row.views.toLocaleString()"></td>
                                            <td class="text-right text-sm" x-text="row.applies.toLocaleString()"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ─────────────────────────────────────────────────────────── --}}
    {{-- Tab: History                                                --}}
    {{-- ─────────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'history'" x-transition>
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="font-semibold text-base mb-4">Lịch sử thay đổi</h2>

                @if($jobPost->histories->isEmpty())
                <p class="text-sm text-base-content/40 text-center py-8">Chưa có lịch sử</p>
                @else
                <div class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead>
                            <tr class="text-xs text-base-content/50 uppercase">
                                <th>Thời gian</th>
                                <th>Loại</th>
                                <th>Trạng thái</th>
                                <th>Người thực hiện</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobPost->histories()->orderByDesc('created_at')->with('changedBy')->get() as $history)
                            <tr class="hover">
                                <td class="text-xs whitespace-nowrap text-base-content/60">
                                    {{ $history->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <span class="badge badge-xs badge-soft badge-info">{{ $history->change_type?->label() }}</span>
                                </td>
                                <td class="text-xs">
                                    @if($history->old_status || $history->new_status)
                                    <span class="text-base-content/50">{{ $history->old_status?->label() }}</span>
                                    @if($history->old_status && $history->new_status)
                                    <span class="text-base-content/30 mx-1">→</span>
                                    @endif
                                    <span>{{ $history->new_status?->label() }}</span>
                                    @else
                                    <span class="text-base-content/30">—</span>
                                    @endif
                                </td>
                                <td class="text-sm">{{ $history->changedBy?->name ?? '—' }}</td>
                                <td class="text-xs text-base-content/60">{{ $history->note ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

</div>

<script>
const JP_POST_ID = {{ $jobPost->id }};
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function jpFetch(url, opts = {}) {
    return fetch(url, {
        ...opts,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Content-Type': 'application/json',
            ...(opts.headers ?? {}),
        },
    });
}

function skillsManager(jobPostId) {
    return {
        skills: [],
        masters: [],
        loading: false,
        saving: false,
        error: '',
        showDropdown: false,
        suggestions: [],
        form: { skillName: '', skillId: null, requirementLevel: 'required', proficiency: '', minYears: '' },

        async load() {
            if (this.loading) return;
            this.loading = true;
            const [skills, masters] = await Promise.all([
                jpFetch(`/backend/api/job-posts/${jobPostId}/skills`).then(r => r.json()),
                jpFetch('/backend/api/skill-masters').then(r => r.json()),
            ]);
            this.skills  = skills;
            this.masters = masters;
            this.loading = false;
        },

        searchSkills() {
            const q = this.form.skillName.trim().toLowerCase();
            if (!q) { this.suggestions = []; this.showDropdown = false; return; }
            this.form.skillId = null;
            this.suggestions = this.masters.filter(s => s.name.toLowerCase().includes(q)).slice(0, 8);
            this.showDropdown = this.suggestions.length > 0;
        },

        selectSuggestion(s) {
            this.form.skillName = s.name;
            this.form.skillId   = s.id;
            this.showDropdown   = false;
        },

        async addSkill() {
            if (!this.form.skillName.trim() || this.saving) return;
            this.saving = true;
            this.error  = '';
            const r = await jpFetch(`/backend/api/job-posts/${jobPostId}/skills`, {
                method: 'POST',
                body: JSON.stringify({
                    skill_name:        this.form.skillName.trim(),
                    skill_id:          this.form.skillId || null,
                    requirement_level: this.form.requirementLevel,
                    proficiency:       this.form.proficiency || null,
                    min_years:         this.form.minYears || null,
                }),
            });
            const data = await r.json();
            if (r.ok) {
                this.skills.push(data);
                this.form = { skillName: '', skillId: null, requirementLevel: 'required', proficiency: '', minYears: '' };
                this.suggestions = []; this.showDropdown = false;
            } else {
                this.error = data.message ?? 'Có lỗi xảy ra.';
            }
            this.saving = false;
        },

        async removeSkill(id) {
            if (!confirm('Xóa kỹ năng này?')) return;
            await jpFetch(`/backend/api/job-posts/${jobPostId}/skills/${id}`, { method: 'DELETE' });
            this.skills = this.skills.filter(s => s.id !== id);
        },

        get required()    { return this.skills.filter(s => s.requirement_level === 'required'); },
        get preferred()   { return this.skills.filter(s => s.requirement_level === 'preferred'); },
        get niceToHave()  { return this.skills.filter(s => s.requirement_level === 'nice_to_have'); },
    };
}

function benefitsManager(jobPostId) {
    const CAT_LABELS = { health: 'Sức khỏe', finance: 'Tài chính', learning: 'Học tập & Phát triển', work_life: 'Work-Life', equipment: 'Thiết bị', other: 'Khác' };

    return {
        benefits: [],
        catalog: [],
        loading: false,
        saving: false,
        showCatalog: false,
        customName: '',

        async load() {
            if (this.loading) return;
            this.loading = true;
            const [benefits, catalog] = await Promise.all([
                jpFetch(`/backend/api/job-posts/${jobPostId}/benefits`).then(r => r.json()),
                jpFetch('/backend/api/benefit-masters').then(r => r.json()),
            ]);
            this.benefits = benefits;
            this.catalog  = catalog;
            this.loading  = false;
        },

        get catalogCategories() {
            return [...new Set(this.catalog.map(b => b.category))];
        },

        catalogByCategory(cat) {
            return this.catalog.filter(b => b.category === cat);
        },

        catLabel(cat) { return CAT_LABELS[cat] ?? cat; },

        isAdded(benefitId) {
            return this.benefits.some(b => b.benefit_id === benefitId);
        },

        async addFromCatalog(master) {
            if (this.isAdded(master.id) || this.saving) return;
            this.saving = true;
            const r = await jpFetch(`/backend/api/job-posts/${jobPostId}/benefits`, {
                method: 'POST',
                body: JSON.stringify({ benefit_id: master.id, benefit_name: master.name }),
            });
            if (r.ok) {
                const data = await r.json();
                this.benefits.push(data);
            }
            this.saving = false;
        },

        async addCustom() {
            if (!this.customName.trim() || this.saving) return;
            this.saving = true;
            const r = await jpFetch(`/backend/api/job-posts/${jobPostId}/benefits`, {
                method: 'POST',
                body: JSON.stringify({ benefit_name: this.customName.trim() }),
            });
            if (r.ok) {
                const data = await r.json();
                this.benefits.push(data);
                this.customName = '';
            }
            this.saving = false;
        },

        async removeBenefit(id) {
            if (!confirm('Xóa phúc lợi này?')) return;
            await jpFetch(`/backend/api/job-posts/${jobPostId}/benefits/${id}`, { method: 'DELETE' });
            this.benefits = this.benefits.filter(b => b.id !== id);
        },
    };
}

function analyticsManager(jobPostId) {
    const SOURCE_LABELS = {
        direct: 'Trực tiếp', marketplace: 'Marketplace',
        career_page: 'Career Page', linkedin: 'LinkedIn',
        referral: 'Referral', other: 'Khác',
    };

    return {
        loading: false,
        loaded: false,
        totals: {},
        bySource: [],
        daily: [],

        async load() {
            if (this.loading) return;
            this.loading = true;
            const r = await jpFetch(`/backend/api/job-posts/${jobPostId}/analytics`);
            if (r.ok) {
                const data = await r.json();
                this.totals   = data.totals;
                this.bySource = data.by_source;
                this.daily    = data.daily;
            }
            this.loaded  = true;
            this.loading = false;
        },

        sourceLabel(source) {
            return SOURCE_LABELS[source] ?? source;
        },
    };
}

function questionsManager(jobPostId) {
    const emptyForm = () => ({
        questionText: '',
        questionType: 'yes_no',
        isRequired: true,
        isDisqualifying: false,
        disqualifyIfAnswer: 'no',
        placeholder: '',
        maxLength: null,
        choices: [],
    });

    return {
        questions: [],
        loading: false,
        saving: false,
        showForm: false,
        editingId: null,
        form: emptyForm(),
        formError: '',

        async load() {
            if (this.loading) return;
            this.loading = true;
            this.questions = await jpFetch(`/backend/api/job-posts/${jobPostId}/questions`).then(r => r.json());
            this.loading = false;
        },

        openAddForm() {
            this.editingId = null;
            this.form = emptyForm();
            this.formError = '';
            this.showForm = true;
        },

        editQuestion(q) {
            this.editingId = q.id;
            this.form = {
                questionText:       q.question_text,
                questionType:       q.question_type,
                isRequired:         q.is_required,
                isDisqualifying:    q.is_disqualifying,
                disqualifyIfAnswer: q.disqualify_if_answer ?? 'no',
                placeholder:        q.placeholder ?? '',
                maxLength:          q.max_length ?? null,
                choices:            (q.choices ?? []).map(c => ({ id: c.id, choice_text: c.choice_text, is_disqualifying: c.is_disqualifying })),
            };
            this.formError = '';
            this.showForm = true;
        },

        closeForm() {
            this.showForm = false;
            this.editingId = null;
            this.form = emptyForm();
        },

        buildPayload() {
            return {
                question_text:        this.form.questionText.trim(),
                question_type:        this.form.questionType,
                is_required:          this.form.isRequired,
                is_disqualifying:     this.form.isDisqualifying,
                disqualify_if_answer: (this.form.isDisqualifying && this.form.questionType === 'yes_no') ? this.form.disqualifyIfAnswer : null,
                placeholder:          this.form.placeholder || null,
                max_length:           this.form.maxLength || null,
                choices: ['single_choice', 'multiple_choice'].includes(this.form.questionType) ? this.form.choices : [],
            };
        },

        async saveQuestion() {
            if (!this.form.questionText.trim() || this.saving) return;
            this.saving = true;
            this.formError = '';

            const url    = this.editingId
                ? `/backend/api/job-posts/${jobPostId}/questions/${this.editingId}`
                : `/backend/api/job-posts/${jobPostId}/questions`;
            const method = this.editingId ? 'PUT' : 'POST';

            const r    = await jpFetch(url, { method, body: JSON.stringify(this.buildPayload()) });
            const data = await r.json();

            if (r.ok) {
                if (this.editingId) {
                    const idx = this.questions.findIndex(q => q.id === this.editingId);
                    if (idx !== -1) this.questions.splice(idx, 1, data);
                } else {
                    this.questions.push(data);
                }
                this.closeForm();
            } else {
                this.formError = data.message ?? 'Có lỗi xảy ra.';
            }
            this.saving = false;
        },

        async removeQuestion(id) {
            if (!confirm('Xóa câu hỏi này?')) return;
            await jpFetch(`/backend/api/job-posts/${jobPostId}/questions/${id}`, { method: 'DELETE' });
            this.questions = this.questions.filter(q => q.id !== id);
        },

        async moveUp(idx) {
            if (idx === 0) return;
            [this.questions[idx - 1], this.questions[idx]] = [this.questions[idx], this.questions[idx - 1]];
            await this.syncOrder();
        },

        async moveDown(idx) {
            if (idx === this.questions.length - 1) return;
            [this.questions[idx], this.questions[idx + 1]] = [this.questions[idx + 1], this.questions[idx]];
            await this.syncOrder();
        },

        async syncOrder() {
            await jpFetch(`/backend/api/job-posts/${jobPostId}/questions/reorder`, {
                method: 'PUT',
                body: JSON.stringify({ ids: this.questions.map(q => q.id) }),
            });
        },
    };
}
</script>
@endsection
