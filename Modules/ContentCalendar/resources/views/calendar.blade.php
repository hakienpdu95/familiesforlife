@extends('layouts.backend')
@section('title', 'Lịch biên tập — Lịch tháng')

@section('content')
<div x-data="contentCalendarMonthView({{ Js::from([
    'listUrl'                => route('backend.api.contentcalendar.entries.list'),
    'storeUrl'               => route('backend.api.contentcalendar.entries.store'),
    'updateUrlTemplate'      => route('backend.api.contentcalendar.entries.update', ['entry' => '__UUID__']),
    'statusUrlTemplate'      => route('backend.api.contentcalendar.entries.change-status', ['entry' => '__UUID__']),
    'linkArticleUrlTemplate' => route('backend.api.contentcalendar.entries.link-article', ['entry' => '__UUID__']),
    'destroyUrlTemplate'     => route('backend.api.contentcalendar.entries.destroy', ['entry' => '__UUID__']),
    'boardUrl'               => route('backend.contentcalendar.board'),
    'categories'             => $categories,
    'assignableUsers'        => $assignableUsers,
    'statuses'               => $statuses,
    'origins'                => $origins,
    'canManage'              => $canManage,
]) }})" x-init="init()">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="mb-4 flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Lịch biên tập</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Kế hoạch nội dung theo ngày dự kiến đăng — xem theo cột trạng thái ở
                <a :href="boardUrl" class="link link-primary">trang Board</a>.
            </p>
        </div>
        <button type="button" class="btn btn-primary btn-sm" x-show="canManage" x-cloak @click="openCreateModal(null)">+ Tạo kế hoạch</button>
    </div>

    {{-- ── Thanh điều hướng tháng ──────────────────────────────────────── --}}
    <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
        <div class="flex items-center gap-1">
            <button type="button" class="btn btn-sm btn-square btn-ghost" @click="goToMonth(-1)" aria-label="Tháng trước">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6"/></svg>
            </button>
            <span class="min-w-36 text-center font-semibold text-base-content" x-text="monthLabel()"></span>
            <button type="button" class="btn btn-sm btn-square btn-ghost" @click="goToMonth(1)" aria-label="Tháng sau">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" class="btn btn-ghost btn-sm" @click="goToToday()">Hôm nay</button>
            <input type="month" x-model="selectedMonth" @change="renderMonth()" class="input input-sm input-bordered">
        </div>
    </div>

    {{-- ── Chú giải màu trạng thái ─────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mb-3 text-xs text-base-content/60">
        <template x-for="s in statuses" :key="s.value">
            <span class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="dotClass(s.value)"></span>
                <span x-text="s.label"></span>
            </span>
        </template>
    </div>

    <p x-show="loading" class="text-sm text-base-content/50 mb-2">Đang tải...</p>

    {{-- ── Chưa xếp lịch ───────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4" x-show="!loading && unscheduled.length > 0" x-cloak>
        <div class="card-body p-3">
            <p class="text-xs font-semibold text-base-content/60 mb-2 uppercase tracking-wide">
                Chưa xếp lịch <span class="badge badge-sm badge-ghost" x-text="unscheduled.length"></span>
            </p>
            <div class="flex flex-wrap gap-1.5">
                <template x-for="entry in unscheduled" :key="entry.uuid">
                    <button type="button"
                            class="flex items-center gap-1.5 rounded-lg border px-2 py-1 text-xs font-medium hover:brightness-95 transition"
                            :class="chipClass(entry.status)"
                            @click="openEditModal(entry)">
                        <span class="truncate max-w-56" x-text="entry.title"></span>
                        <span class="opacity-60 text-[10px]" x-text="entry.category?.name"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- ── Lưới lịch tháng ─────────────────────────────────────────────── --}}
    <div class="rounded-xl border border-base-200 overflow-hidden bg-base-200">
        <div class="grid grid-cols-7 gap-px bg-base-200">
            <template x-for="wd in weekdayLabels" :key="wd">
                <div class="bg-base-100 py-2 text-center text-xs font-semibold text-base-content/50">
                    <span x-text="wd"></span>
                </div>
            </template>
        </div>

        <div class="grid grid-cols-7 gap-px bg-base-200">
            <template x-for="(cell, idx) in weeksFlat" :key="idx">
                <div class="group relative min-h-28 p-1.5 flex flex-col gap-1 transition"
                     :class="cell.inMonth ? 'bg-base-100' : 'bg-base-200/40'">

                    <div class="flex items-center justify-between">
                        <span class="w-6 h-6 flex items-center justify-center rounded-full text-xs font-semibold"
                              :class="cell.isToday ? 'bg-primary text-primary-content' : (cell.inMonth ? 'text-base-content/70' : 'text-base-content/30')"
                              x-text="cell.day"></span>
                        <button type="button" x-show="canManage && cell.inMonth" x-cloak
                                class="w-5 h-5 rounded flex items-center justify-center text-base-content/40 opacity-0 group-hover:opacity-100 hover:bg-base-200 hover:text-base-content transition"
                                title="Tạo kế hoạch cho ngày này"
                                @click="openCreateModal(cell.dateStr)">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>

                    <div class="flex-1 min-h-0 flex flex-col gap-1 overflow-y-auto max-h-24">
                        <template x-for="entry in cell.entries" :key="entry.uuid">
                            <button type="button"
                                    class="w-full text-left truncate rounded px-1.5 py-0.5 text-[11px] font-medium border hover:brightness-95 transition"
                                    :class="chipClass(entry.status)"
                                    :title="entry.title"
                                    @click="openEditModal(entry)"
                                    x-text="entry.title"></button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <p x-show="!loading && entries.length === 0" x-cloak class="text-sm text-base-content/40 text-center py-10">
        Chưa có kế hoạch nào — bấm "+ Tạo kế hoạch" để bắt đầu.
    </p>

    {{-- ── Modal tạo/sửa entry ─────────────────────────────────────────── --}}
    <dialog x-ref="entryDialog" class="modal">
        <div class="modal-box max-w-2xl">
            <h3 class="font-bold text-lg" x-text="editingEntry ? 'Sửa kế hoạch' : 'Tạo kế hoạch mới'"></h3>

            {{-- docs/form-ui-spec.md §13.1 — grid 2 cột desktop, field full-width dùng sm:col-span-2.
                 §14 — cấu trúc form-control bắt buộc (label + hint + input + error). --}}
            <form @submit.prevent="submitForm()" novalidate class="mt-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tiêu đề <span class="text-error">*</span></span>
                        </label>
                        <input type="text" x-model="form.title" required maxlength="255"
                               placeholder="VD: 7 mẹo giúp bé ăn dặm không quấy khóc"
                               class="input input-bordered input-sm w-full"
                               :class="fieldErrors.title ? 'input-error' : ''">
                        <p x-show="fieldErrors.title" x-cloak class="mt-1 text-xs text-error" x-text="fieldErrors.title?.[0]"></p>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Category <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-entry-category" x-model="form.post_category_id" required
                                class="select select-bordered select-sm w-full"
                                :class="fieldErrors.post_category_id ? 'select-error' : ''"
                                data-ts-placeholder="— Chọn category —">
                            <option value="" disabled>— Chọn category —</option>
                            <template x-for="cat in categories" :key="cat.id">
                                <option :value="cat.id" :disabled="!cat.selectable" x-text="categoryOptionLabel(cat)"></option>
                            </template>
                        </select>
                        <p x-show="fieldErrors.post_category_id" x-cloak class="mt-1 text-xs text-error" x-text="fieldErrors.post_category_id?.[0]"></p>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Nguồn gốc ý tưởng <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-entry-origin" x-model="form.origin"
                                class="select select-bordered select-sm w-full"
                                data-ts-placeholder="— Chọn nguồn gốc —">
                            <template x-for="o in origins" :key="o.value">
                                <option :value="o.value" x-text="o.label"></option>
                            </template>
                        </select>
                        <p x-show="fieldErrors.origin" x-cloak class="mt-1 text-xs text-error" x-text="fieldErrors.origin?.[0]"></p>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Ngày dự kiến đăng</span>
                            <span class="label-text-alt text-base-content/40 text-xs">Không bắt buộc</span>
                        </label>
                        <input type="text" id="fp-entry-target-publish-date" x-model="form.target_publish_date"
                               placeholder="dd/mm/yyyy"
                               class="input input-bordered input-sm w-full"
                               :class="fieldErrors.target_publish_date ? 'input-error' : ''">
                        <p x-show="fieldErrors.target_publish_date" x-cloak class="mt-1 text-xs text-error" x-text="fieldErrors.target_publish_date?.[0]"></p>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Người phụ trách</span>
                            <span class="label-text-alt text-base-content/40 text-xs">Không bắt buộc</span>
                        </label>
                        <select id="ts-entry-assignee" x-model="form.assigned_to"
                                class="select select-bordered select-sm w-full"
                                data-ts-placeholder="— Chưa gán —">
                            <option value="">— Chưa gán —</option>
                            <template x-for="u in assignableUsers" :key="u.id">
                                <option :value="u.id" x-text="u.name"></option>
                            </template>
                        </select>
                        <p x-show="fieldErrors.assigned_to" x-cloak class="mt-1 text-xs text-error" x-text="fieldErrors.assigned_to?.[0]"></p>
                    </div>
                </div>

                {{-- Textarea ngoài grid — §13.1 "Textarea, rich text: Full (ngoài grid)". --}}
                <div class="form-control mt-4">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Tóm tắt / góc nhìn</span>
                        <span class="label-text-alt text-base-content/40 text-xs">Không bắt buộc</span>
                    </label>
                    <textarea x-model="form.brief" rows="2" maxlength="2000"
                              placeholder="Góc nhìn riêng / tóm tắt ngắn — không phải nội dung bài"
                              class="textarea textarea-bordered textarea-sm w-full"
                              :class="fieldErrors.brief ? 'textarea-error' : ''"></textarea>
                    <p x-show="fieldErrors.brief" x-cloak class="mt-1 text-xs text-error" x-text="fieldErrors.brief?.[0]"></p>
                </div>

                <div class="form-control mt-4" x-show="form.origin !== 'manual'" x-cloak>
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Ghi chú nguồn gốc <span class="text-error">*</span></span>
                        <span class="label-text-alt text-base-content/40 text-xs">Dán ý tưởng + lý do</span>
                    </label>
                    <textarea x-model="form.origin_note" rows="3" maxlength="5000"
                              placeholder="VD: dán nguyên dòng ý tưởng + lý do từ bảng Layer 2 CoreIdeaExtractor"
                              class="textarea textarea-bordered textarea-sm w-full font-mono text-xs"
                              :class="fieldErrors.origin_note ? 'textarea-error' : ''"
                              :required="form.origin !== 'manual'"></textarea>
                    <p x-show="fieldErrors.origin_note" x-cloak class="mt-1 text-xs text-error" x-text="fieldErrors.origin_note?.[0]"></p>
                </div>

                {{-- ── Hành động nhanh trên entry đã có (không áp dụng khi đang tạo mới) ── --}}
                <div class="flex flex-wrap items-center gap-2 border-t border-base-200 mt-4 pt-3" x-show="editingEntry" x-cloak>
                    <span class="badge" :class="editingEntry?.status_badge_class" x-text="editingEntry?.status_label"></span>

                    <select class="select select-xs select-bordered" x-show="editingEntry?.can_manage" @change="changeStatus($event.target.value); $event.target.value=''">
                        <option value="">Chuyển sang...</option>
                        <template x-for="opt in nextStatusOptions(editingEntry)" :key="opt.value">
                            <option :value="opt.value" x-text="opt.label"></option>
                        </template>
                    </select>

                    <button type="button" class="btn btn-2xs btn-ghost" x-show="editingEntry?.can_manage && !editingEntry?.is_linked" @click="promptLinkArticle()">Gắn bài viết</button>
                    <a x-show="editingEntry?.is_linked" href="#" class="btn btn-2xs btn-ghost" @click.prevent="alert('post_article uuid: ' + editingEntry.post_article.uuid)">Xem bài viết</a>
                    <button type="button" class="btn btn-2xs btn-ghost text-error ml-auto" x-show="editingEntry?.can_delete" @click="deleteEntry()">Xoá kế hoạch</button>
                </div>

                {{-- docs/form-ui-spec.md §20 — Submit Actions Bar, phương án "có validation state". --}}
                <div class="flex items-center gap-3 pt-4 mt-4 border-t border-base-200">
                    <div x-show="errorMessage" x-cloak x-transition class="flex items-center gap-2 text-sm text-error">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                        <span x-text="errorMessage"></span>
                    </div>
                    <div class="ml-auto flex gap-2">
                        <button type="button" class="btn btn-ghost btn-sm" @click="closeModal()">Đóng</button>
                        <button type="submit" class="btn btn-primary btn-sm gap-2" :disabled="saving" :class="{ 'btn-disabled': saving }">
                            <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                            <span x-text="saving ? 'Đang lưu...' : (editingEntry ? 'Lưu thay đổi' : 'Tạo kế hoạch')"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

</div>
@endsection

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'Modules/ContentCalendar/resources/assets/js/content-calendar.js',
    ], 'build/backend')
@endpush
