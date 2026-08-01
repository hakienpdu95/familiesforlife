@extends('layouts.backend')
@section('title', 'Lịch biên tập — Board')

@section('content')
<div x-data="contentCalendarBoard({{ Js::from([
    'listUrl'          => route('backend.api.contentcalendar.entries.list'),
    'storeUrl'         => route('backend.api.contentcalendar.entries.store'),
    'updateUrlTemplate'      => route('backend.api.contentcalendar.entries.update', ['entry' => '__UUID__']),
    'statusUrlTemplate'      => route('backend.api.contentcalendar.entries.change-status', ['entry' => '__UUID__']),
    'linkArticleUrlTemplate' => route('backend.api.contentcalendar.entries.link-article', ['entry' => '__UUID__']),
    'destroyUrlTemplate'     => route('backend.api.contentcalendar.entries.destroy', ['entry' => '__UUID__']),
    'calendarUrl'      => route('backend.contentcalendar.calendar'),
    'categories'       => $categories,
    'assignableUsers'  => $assignableUsers,
    'statuses'         => $statuses,
    'origins'          => $origins,
    'canManage'        => $canManage,
]) }})" x-init="init()">

    <div class="mb-4 flex items-start justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Lịch biên tập — Board</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Hàng đợi ý tưởng đã chọn + tiến độ, nối giữa CoreIdeaExtractor/Aicem và bài viết thật (Post).
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('backend.contentcalendar.calendar') }}" class="btn btn-ghost btn-sm">Xem theo lịch tháng</a>
            <button type="button" class="btn btn-primary btn-sm" x-show="canManage" x-cloak @click="openCreateModal()">+ Tạo kế hoạch</button>
        </div>
    </div>

    {{-- ── Bộ lọc ──────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-3 flex flex-row flex-wrap gap-3 items-end">
            <div class="form-control">
                <label class="label py-0.5"><span class="label-text text-xs font-medium">Category</span></label>
                <select x-model="filters.categoryId" @change="loadEntries()" class="select select-sm select-bordered w-64">
                    <option value="">Tất cả</option>
                    <template x-for="cat in categories" :key="cat.id">
                        <option :value="cat.id" x-text="categoryOptionLabel(cat)"></option>
                    </template>
                </select>
            </div>
            <div class="form-control">
                <label class="label py-0.5"><span class="label-text text-xs font-medium">Người phụ trách</span></label>
                <select x-model="filters.assignedTo" @change="loadEntries()" class="select select-sm select-bordered w-52">
                    <option value="">Tất cả</option>
                    <template x-for="u in assignableUsers" :key="u.id">
                        <option :value="u.id" x-text="u.name"></option>
                    </template>
                </select>
            </div>
            <label class="label cursor-pointer gap-2">
                <input type="checkbox" x-model="filters.includeDone" @change="loadEntries()" class="checkbox checkbox-sm">
                <span class="label-text text-xs">Hiện cả Đã xong/Đã huỷ</span>
            </label>
            <p x-show="loading" class="text-xs text-base-content/40">Đang tải...</p>
        </div>
    </div>

    {{-- ── Board Kanban ────────────────────────────────────────────────── --}}
    <div class="flex gap-3 overflow-x-auto pb-2">
        <template x-for="col in statuses" :key="col.value">
            <div class="w-72 shrink-0 flex flex-col bg-base-100 border border-base-200 rounded-lg">
                <div class="px-3 py-2 border-b border-base-200 flex items-center justify-between">
                    <span class="font-semibold text-sm" x-text="col.label"></span>
                    <span class="badge badge-sm" :class="col.badge" x-text="entriesByStatus(col.value).length"></span>
                </div>
                <div class="flex-1 min-h-24 p-2 flex flex-col gap-2 max-h-[70vh] overflow-y-auto">
                    <template x-for="entry in entriesByStatus(col.value)" :key="entry.uuid">
                        <div class="card bg-base-200/40 border border-base-200 shadow-none">
                            <div class="card-body p-2.5 gap-1.5">
                                <p class="text-sm font-medium leading-snug" x-text="entry.title"></p>
                                <div class="flex flex-wrap gap-1 items-center">
                                    <span class="badge badge-ghost badge-xs" x-text="entry.category?.name"></span>
                                    <span class="badge badge-xs" :class="entry.status_badge_class" x-text="entry.status_label"
                                          :title="entry.is_linked ? 'Đọc từ trạng thái bài viết thật — không sửa trực tiếp được' : ''"></span>
                                </div>
                                <p class="text-xs text-base-content/50" x-show="entry.assigned_to">
                                    Người viết: <span x-text="entry.assigned_to?.name"></span>
                                </p>
                                <p class="text-xs text-base-content/50" x-show="entry.target_publish_date">
                                    Dự kiến: <span x-text="entry.target_publish_date"></span>
                                </p>

                                <div class="flex flex-wrap gap-1 mt-1">
                                    <a x-show="entry.is_linked" x-cloak :href="'#'" class="btn btn-2xs btn-ghost" @click.prevent="alert('post_article uuid: ' + entry.post_article.uuid)">Xem bài viết</a>

                                    <template x-if="entry.can_manage">
                                        <select class="select select-2xs select-bordered" @change="changeStatus(entry, $event.target.value); $event.target.value=''">
                                            <option value="">Chuyển sang...</option>
                                            <template x-for="opt in nextStatusOptions(entry)" :key="opt.value">
                                                <option :value="opt.value" x-text="opt.label"></option>
                                            </template>
                                        </select>
                                    </template>

                                    <button type="button" class="btn btn-2xs btn-ghost" x-show="entry.can_manage" @click="openEditModal(entry)">Sửa</button>
                                    <button type="button" class="btn btn-2xs btn-ghost" x-show="entry.can_manage && !entry.is_linked" @click="promptLinkArticle(entry)">Gắn bài viết</button>
                                    <button type="button" class="btn btn-2xs btn-ghost text-error" x-show="entry.can_delete" @click="deleteEntry(entry)">Xoá</button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <p x-show="entriesByStatus(col.value).length === 0" class="text-xs text-base-content/30 text-center py-4">Trống</p>
                </div>
            </div>
        </template>
    </div>

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

                {{-- docs/form-ui-spec.md §20 — Submit Actions Bar, phương án "có validation state". --}}
                <div class="flex items-center gap-3 pt-4 mt-4 border-t border-base-200">
                    <div x-show="errorMessage" x-cloak x-transition class="flex items-center gap-2 text-sm text-error">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                        <span x-text="errorMessage"></span>
                    </div>
                    <div class="ml-auto flex gap-2">
                        <button type="button" class="btn btn-ghost btn-sm" @click="closeModal()">Huỷ</button>
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
