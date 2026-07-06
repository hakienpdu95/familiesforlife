@extends('layouts.backend')
@section('title', $kcItem->title)


@section('content')

{{-- ── Page header ──────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2 mb-1">
            @php
                $status = $kcItem->status instanceof \Modules\KcItem\Enums\KcItemStatus
                    ? $kcItem->status
                    : \Modules\KcItem\Enums\KcItemStatus::from($kcItem->status);
                $type = $kcItem->type instanceof \Modules\KcItem\Enums\KcItemType
                    ? $kcItem->type
                    : \Modules\KcItem\Enums\KcItemType::from($kcItem->type);
            @endphp
            <span class="badge badge-{{ $status->color() }} badge-sm">{{ $status->label() }}</span>
            <span class="badge badge-outline badge-sm">{{ $type->label() }}</span>
            @if($kcItem->is_featured)<span class="badge badge-warning badge-sm badge-soft">Nổi bật</span>@endif
            @if($kcItem->is_pinned)<span class="badge badge-info badge-sm badge-soft">Ghim</span>@endif
            {{-- Avg rating --}}
            @if($feedbackSummary['avg_rating'])
            <span class="badge badge-ghost badge-sm gap-1">
                <svg class="w-3 h-3 text-warning" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                {{ number_format($feedbackSummary['avg_rating'], 1) }}
                <span class="text-base-content/40">({{ $feedbackSummary['total'] }})</span>
            </span>
            @endif
        </div>
        <h1 class="text-2xl font-bold text-base-content">{{ $kcItem->title }}</h1>
        <div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-base-content/50">
            @if($kcItem->category)
                <span class="flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    {{ $kcItem->category->name }}
                </span>
            @endif
            @if($kcItem->owner)
                <span>bởi <strong>{{ $kcItem->owner->name }}</strong></span>
            @endif
            <span>v{{ $kcItem->version }}</span>
            <span>{{ $kcItem->view_count }} lượt xem</span>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 shrink-0">

        @if($canSubmit)
        <form method="POST" action="{{ route('backend.kc-items.submit', $kcItem) }}"
              onsubmit="return confirm('Gửi tài liệu này để duyệt?')">
            @csrf
            <button type="submit" class="btn btn-warning btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Gửi duyệt
            </button>
        </form>
        @endif

        @if($canApprove)
        <form method="POST" action="{{ route('backend.kc-items.approve', $kcItem) }}"
              onsubmit="return confirm('Phê duyệt tài liệu này?')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Duyệt
            </button>
        </form>
        @endif

        @if($canReject)
        <button onclick="kcItemRejectModal.showModal()" class="btn btn-error btn-outline btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            Từ chối
        </button>
        @endif

        @can('update', $kcItem)
        @if($kcItem->isEditable())
        <a href="{{ route('backend.kc-items.edit', $kcItem) }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Chỉnh sửa
        </a>
        @endif
        @endcan

    </div>
</div>

{{-- ── Content tabs + Sidebar ─────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_280px] gap-6 items-start">

    {{-- Main area with tabs --}}
    <div x-data="{ tab: 'content' }">

        {{-- Tab navigation --}}
        <div class="tabs tabs-bordered mb-5">
            <button @click="tab='content'" :class="tab==='content' ? 'tab-active' : ''" class="tab gap-1.5">
                Nội dung
            </button>
            @if($kcItem->versionHistories->isNotEmpty())
            <button @click="tab='versions'" :class="tab==='versions' ? 'tab-active' : ''" class="tab gap-1.5">
                Phiên bản
                <span class="badge badge-ghost badge-xs">{{ $kcItem->versionHistories->count() }}</span>
            </button>
            @endif
            @if($canManageAccess)
            <button @click="tab='access'" :class="tab==='access' ? 'tab-active' : ''" class="tab gap-1.5">
                Phân quyền
            </button>
            @endif
            <button @click="tab='feedback'" :class="tab==='feedback' ? 'tab-active' : ''" class="tab gap-1.5">
                Đánh giá
                @if($feedbackSummary['total'] > 0)
                <span class="badge badge-ghost badge-xs">{{ $feedbackSummary['total'] }}</span>
                @endif
            </button>
        </div>

        {{-- Tab: Nội dung --}}
        <div x-show="tab==='content'" class="space-y-5">

            @if($kcItem->summary)
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="card-title text-sm text-base-content/60">Tóm tắt</h2>
                    <p class="text-sm text-base-content/80 leading-relaxed">{{ $kcItem->summary }}</p>
                </div>
            </div>
            @endif

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="card-title text-base mb-3">Nội dung</h2>
                    @if($kcItem->content)
                    <div class="prose prose-sm max-w-none text-base-content">
                        {!! nl2br(e($kcItem->content)) !!}
                    </div>
                    @else
                    <p class="text-sm text-base-content/40 italic">Chưa có nội dung.</p>
                    @endif
                </div>
            </div>

            @if($kcItem->tags->isNotEmpty())
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-2">Tags</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($kcItem->tags as $tag)
                        <a href="{{ route('backend.kc-items.index', ['tag' => $tag->id]) }}"
                           class="badge badge-sm font-medium gap-1 hover:opacity-80 transition-opacity"
                           style="background-color: {{ $tag->color_hex ?? '#6366f1' }}; color: #fff; border-color: transparent;">
                            {{ $tag->name }}
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Tab: Phiên bản --}}
        <div x-show="tab==='versions'" class="space-y-3">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="card-title text-base mb-4">Lịch sử phiên bản</h2>
                    <div class="space-y-2">
                        @foreach($kcItem->versionHistories as $ver)
                        <div class="flex items-start gap-3 p-3 rounded-lg border border-base-200 hover:border-base-300 transition-colors">
                            <span class="badge badge-ghost badge-sm font-mono shrink-0 mt-0.5">v{{ $ver->version_number }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium">{{ Str::limit($ver->title_snapshot, 60) }}</p>
                                @if($ver->change_summary)
                                <p class="text-xs text-base-content/50 mt-0.5">{{ $ver->change_summary }}</p>
                                @endif
                                <p class="text-xs text-base-content/40 mt-1">
                                    {{ $ver->changedBy?->name }} — {{ $ver->changed_at?->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <a href="{{ route('backend.kc-items.versions.show', [$kcItem, $ver->version_number]) }}"
                                   class="btn btn-ghost btn-xs gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Xem
                                </a>
                                @if($canRollback && $ver->version_number !== $kcItem->version)
                                <form method="POST" action="{{ route('backend.kc-items.rollback', [$kcItem, $ver->version_number]) }}"
                                      onsubmit="return confirm('Rollback về version {{ $ver->version_number }}? Tài liệu sẽ trở về draft.')">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-xs gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                        Rollback
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab: Phân quyền --}}
        @if($canManageAccess)
        <div x-show="tab==='access'"
             x-data="kcAccessControl({
                 indexUrl:   '{{ route('backend.api.kc-items.permissions.index', $kcItem) }}',
                 storeUrl:   '{{ route('backend.api.kc-items.permissions.store', $kcItem) }}',
                 destroyBase: '{{ rtrim(route('backend.api.kc-items.permissions.destroy', [$kcItem, '__ID__']), '__ID__') }}',
             })">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="card-title text-base mb-4">Phân quyền truy cập</h2>

                    @if($kcItem->visibility !== \Modules\KcItem\Enums\KcItemVisibility::Restricted)
                    <div class="alert alert-info text-sm mb-4">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Phân quyền chi tiết chỉ có hiệu lực khi Phạm vi hiển thị = <strong>Restricted</strong>. Hiện tại: <strong>{{ $kcItem->visibility instanceof \Modules\KcItem\Enums\KcItemVisibility ? $kcItem->visibility->label() : $kcItem->visibility }}</strong></span>
                    </div>
                    @endif

                    {{-- Form cấp quyền --}}
                    <div class="card bg-base-200/50 border border-base-300 mb-4">
                        <div class="card-body p-4">
                            <p class="text-xs font-semibold text-base-content/60 uppercase tracking-wide mb-3">Cấp quyền mới</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                <div class="form-control">
                                    <label class="label py-0 pb-1"><span class="label-text text-xs">Loại đối tượng</span></label>
                                    <select x-model="form.target_type" class="select select-bordered select-sm">
                                        <option value="user">User cụ thể</option>
                                        <option value="role">Vai trò (Role)</option>
                                        <option value="dept">Phòng ban</option>
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label py-0 pb-1"><span class="label-text text-xs">ID đối tượng</span></label>
                                    <input x-model="form.target_id" type="number" min="1"
                                           class="input input-bordered input-sm"
                                           placeholder="Nhập numeric ID">
                                </div>
                                <div class="form-control">
                                    <label class="label py-0 pb-1"><span class="label-text text-xs">Mức quyền</span></label>
                                    <select x-model="form.permission" class="select select-bordered select-sm">
                                        <option value="view">Chỉ xem</option>
                                        <option value="edit">Xem + sửa</option>
                                        <option value="manage">Toàn quyền</option>
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label py-0 pb-1"><span class="label-text text-xs">Hết hạn (tuỳ chọn)</span></label>
                                    <input x-model="form.expired_at" type="date"
                                           class="input input-bordered input-sm"
                                           :min="new Date().toISOString().split('T')[0]">
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                <button @click="grantAccess()" :disabled="saving" class="btn btn-primary btn-sm gap-1.5">
                                    <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                                    Cấp quyền
                                </button>
                                <span x-show="error" x-text="error" class="text-xs text-error"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Danh sách quyền --}}
                    <div class="space-y-2">
                        <template x-if="loading">
                            <div class="flex justify-center py-4"><span class="loading loading-spinner loading-sm"></span></div>
                        </template>
                        <template x-if="!loading && controls.length === 0">
                            <p class="text-sm text-base-content/40 text-center py-4">Chưa có phân quyền nào.</p>
                        </template>
                        <template x-for="ac in controls" :key="ac.id">
                            <div class="flex items-center gap-3 p-3 rounded-lg border border-base-200 text-sm">
                                <span class="badge badge-ghost badge-sm font-mono shrink-0" x-text="ac.target_type"></span>
                                <span class="font-medium shrink-0" x-text="'ID: ' + ac.target_id"></span>
                                <span class="badge badge-sm shrink-0"
                                      :class="ac.permission === 'manage' ? 'badge-error' : ac.permission === 'edit' ? 'badge-warning' : 'badge-info'"
                                      x-text="ac.permission"></span>
                                <span class="text-xs text-base-content/40 flex-1 truncate" x-text="ac.granted_by ? 'bởi ' + ac.granted_by : ''"></span>
                                <span x-show="ac.expired_at" class="text-xs text-base-content/40 shrink-0" x-text="'HH: ' + ac.expired_at"></span>
                                <button @click="revokeAccess(ac)"
                                        class="btn btn-ghost btn-xs btn-square text-error/40 hover:text-error shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Tab: Đánh giá --}}
        <div x-show="tab==='feedback'"
             x-data="kcFeedback({
                 upsertUrl: '{{ route('backend.api.kc-items.feedback.upsert', $kcItem) }}',
                 initialRating: {{ $myFeedback?->rating ?? 'null' }},
                 initialComment: {{ json_encode($myFeedback?->comment) }},
                 initialHelpful: {{ is_null($myFeedback?->is_helpful) ? 'null' : ($myFeedback->is_helpful ? 'true' : 'false') }},
                 avgRating: {{ $feedbackSummary['avg_rating'] ?? 'null' }},
                 totalFeedback: {{ $feedbackSummary['total'] }},
                 helpfulPercent: {{ $feedbackSummary['helpful_percent'] ?? 'null' }},
             })">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="card-title text-base mb-4">Đánh giá tài liệu</h2>

                    {{-- Summary stats --}}
                    @if($feedbackSummary['total'] > 0)
                    <div class="flex flex-wrap items-center gap-4 p-4 bg-base-200/50 rounded-xl mb-5">
                        <div class="text-center">
                            <div class="text-3xl font-bold" x-text="avgRating ? avgRating.toFixed(1) : '—'"></div>
                            <div class="flex justify-center gap-0.5 mt-1">
                                <template x-for="i in 5" :key="i">
                                    <svg class="w-4 h-4" :class="i <= Math.round(avgRating) ? 'text-warning' : 'text-base-content/20'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </template>
                            </div>
                            <div class="text-xs text-base-content/40 mt-1" x-text="totalFeedback + ' đánh giá'"></div>
                        </div>
                        <div x-show="helpfulPercent !== null" class="text-center">
                            <div class="text-2xl font-bold text-success" x-text="helpfulPercent + '%'"></div>
                            <div class="text-xs text-base-content/40">Hữu ích</div>
                        </div>
                    </div>
                    @endif

                    {{-- My feedback form --}}
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm font-medium mb-2">Đánh giá của bạn</p>
                            <div class="flex gap-1">
                                <template x-for="i in 5" :key="i">
                                    <button type="button" @click="rating = (rating === i ? null : i)"
                                            class="text-2xl transition-transform hover:scale-110"
                                            :class="i <= rating ? 'text-warning' : 'text-base-content/20'">★</button>
                                </template>
                                <button x-show="rating" @click="rating = null" type="button"
                                        class="btn btn-ghost btn-xs ml-1 text-base-content/40">Bỏ</button>
                            </div>
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-sm">Nhận xét</span></label>
                            <textarea x-model="comment" rows="3"
                                      class="textarea textarea-bordered w-full text-sm"
                                      placeholder="Chia sẻ ý kiến về tài liệu này..."></textarea>
                        </div>

                        <div class="flex items-center gap-4">
                            <p class="text-sm font-medium">Tài liệu này có hữu ích không?</p>
                            <div class="flex gap-2">
                                <button type="button" @click="helpful = (helpful === true ? null : true)"
                                        :class="helpful === true ? 'btn-success' : 'btn-ghost'"
                                        class="btn btn-sm gap-1">
                                    👍 Có
                                </button>
                                <button type="button" @click="helpful = (helpful === false ? null : false)"
                                        :class="helpful === false ? 'btn-error' : 'btn-ghost'"
                                        class="btn btn-sm gap-1">
                                    👎 Không
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button @click="submitFeedback()" :disabled="saving" class="btn btn-primary btn-sm gap-1.5">
                                <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                                Lưu đánh giá
                            </button>
                            <span x-show="successMsg" x-text="successMsg" class="text-xs text-success"></span>
                            <span x-show="errorMsg" x-text="errorMsg" class="text-xs text-error"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Sidebar --}}
    <div class="space-y-4">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 space-y-2.5">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Thông tin</p>
                <dl class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Danh mục</dt>
                        <dd class="font-medium">{{ $kcItem->category?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Phạm vi</dt>
                        <dd>{{ $kcItem->visibility instanceof \Modules\KcItem\Enums\KcItemVisibility ? $kcItem->visibility->label() : $kcItem->visibility }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Ngôn ngữ</dt>
                        <dd>{{ strtoupper($kcItem->language ?? 'VI') }}</dd>
                    </div>
                    @if($kcItem->effective_date)
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Hiệu lực từ</dt>
                        <dd>{{ $kcItem->effective_date->format('d/m/Y') }}</dd>
                    </div>
                    @endif
                    @if($kcItem->expired_date)
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Hết hạn</dt>
                        <dd class="{{ $kcItem->expired_date->isPast() ? 'text-error' : '' }}">
                            {{ $kcItem->expired_date->format('d/m/Y') }}
                        </dd>
                    </div>
                    @endif
                    @if($kcItem->approvedBy)
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Người duyệt</dt>
                        <dd>{{ $kcItem->approvedBy->name }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Ngày tạo</dt>
                        <dd>{{ $kcItem->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Cập nhật</dt>
                        <dd>{{ $kcItem->updated_at->diffForHumans() }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200"
             x-data="kcAttachmentManager({
                 uploadUrl: '{{ route('backend.api.kc-items.attachments.store', $kcItem) }}',
                 maxMb: {{ config('kc.attachments.max_file_size_mb', 50) }},
                 maxTotalMb: {{ config('kc.attachments.max_item_total_mb', 200) }},
             })">
            <div class="card-body p-4">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">
                        Đính kèm (<span x-text="files.length">{{ $kcItem->attachments->count() }}</span>)
                    </p>
                    <span class="text-xs text-base-content/30" x-text="totalLabel"></span>
                </div>

                <div id="kc-attach-existing"
                     data-files="{{ json_encode($kcItem->attachments->map(fn($a) => [
                         'id' => $a->id,
                         'file_name' => $a->file_name,
                         'file_url' => $a->file_url,
                         'file_type' => $a->file_type,
                         'file_size_kb' => $a->file_size_kb,
                         'delete_url' => route('backend.api.kc-items.attachments.destroy', [$kcItem, $a]),
                     ])) }}">
                </div>

                <div class="space-y-2">
                    <template x-for="(f, i) in files" :key="f.id || i">
                        <div class="flex items-center gap-2 p-2 rounded-lg border border-base-200 text-xs">
                            <svg class="w-4 h-4 shrink-0 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <a :href="f.file_url" target="_blank" class="flex-1 truncate hover:text-primary" x-text="f.file_name"></a>
                            <span class="text-base-content/30 shrink-0" x-text="formatSize(f.file_size_kb)"></span>
                            @can('update', $kcItem)
                            <button type="button" @click="deleteFile(f, i)"
                                    class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error shrink-0" title="Xóa file">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            @endcan
                        </div>
                    </template>

                    <p x-show="files.length === 0" class="text-xs text-base-content/30 text-center py-2">Chưa có file đính kèm</p>
                </div>

                @can('update', $kcItem)
                <label class="flex items-center gap-2 mt-3 p-2.5 rounded-lg border border-dashed border-base-300 hover:border-primary/50 hover:bg-base-200/50 transition-colors cursor-pointer"
                       @dragover.prevent @drop.prevent="onFileDrop($event)">
                    <input type="file" multiple class="hidden" @change="onFileSelect($event)"
                           accept="{{ implode(',', array_map(fn($e) => '.'.$e, config('kc.attachments.allowed_extensions', []))) }}">
                    <svg class="w-4 h-4 shrink-0 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span class="text-xs text-base-content/50">Thêm file đính kèm</span>
                    <span x-show="uploading" class="loading loading-spinner loading-xs ml-auto"></span>
                </label>
                <p x-show="error" x-text="error" class="text-xs text-error mt-1.5"></p>
                @endcan
            </div>
        </div>

        @can('delete', $kcItem)
        <div class="card bg-base-100 shadow-sm border border-error/20">
            <div class="card-body p-4">
                <p class="text-xs font-semibold text-error/60 uppercase tracking-wide mb-3">Vùng nguy hiểm</p>
                <form method="POST" action="{{ route('backend.kc-items.destroy', $kcItem) }}"
                      onsubmit="return confirm('Xác nhận xóa tài liệu này?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-error btn-sm btn-outline w-full">Xóa tài liệu</button>
                </form>
            </div>
        </div>
        @endcan

    </div>

</div>

@endsection

@push('scripts')
    @vite([
        'Modules/KcItem/resources/assets/js/kc-item.js',
    ], 'build/backend')
@endpush

{{-- Reject modal --}}
@if($canReject)
<dialog id="kcItemRejectModal" class="modal">
    <div class="modal-box max-w-md">
        <h3 class="font-bold text-lg text-error mb-3">Từ chối tài liệu</h3>
        <form method="POST" action="{{ route('backend.kc-items.reject', $kcItem) }}">
            @csrf
            <div class="form-control mb-4">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Lý do từ chối <span class="text-error">*</span></span>
                </label>
                <textarea name="reason" rows="3" required
                          class="textarea textarea-bordered w-full"
                          placeholder="Nêu rõ lý do từ chối để tác giả chỉnh sửa..."></textarea>
            </div>
            <div class="modal-action mt-0">
                <button type="submit" class="btn btn-error btn-sm">Xác nhận từ chối</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="kcItemRejectModal.close()">Hủy</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endif
