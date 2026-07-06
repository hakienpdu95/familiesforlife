{{--
    Vertical Template Builder — Alpine.js component
    Nhận: $template, $phasesData (array)
--}}

<div x-data="verticalTemplateBuilder(@js($phasesData), @js($template->id), @js(csrf_token()))" class="space-y-3">

    {{-- Flash --}}
    <div x-show="flash.text" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         role="alert"
         :class="flash.type === 'error' ? 'alert-error' : 'alert-success'"
         class="alert text-sm py-2 px-4 rounded-lg">
        <span x-text="flash.text"></span>
    </div>

    {{-- Toolbar --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <h2 class="font-bold text-base text-base-content">Phase &amp; Checklist</h2>
            <span class="badge badge-ghost badge-sm" x-text="phases.length + ' phase'"></span>
        </div>
        <div class="flex gap-2 items-center">
            <span x-show="saving" class="loading loading-spinner loading-xs text-primary"></span>
            <button @click="openPhaseModal()" :disabled="saving" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Thêm phase
            </button>
        </div>
    </div>

    {{-- Empty state --}}
    <div x-show="phases.length === 0" class="text-center py-16 border-2 border-dashed border-base-300 rounded-2xl bg-base-100">
        <p class="font-semibold text-base-content/40">Chưa có phase nào</p>
        <p class="text-xs text-base-content/30 mt-1">Nhấn "Thêm phase" để bắt đầu</p>
    </div>

    {{-- Phase list --}}
    <template x-for="(phase, pIdx) in phases" :key="phase.id">
        <div class="rounded-xl border border-base-200 bg-base-100 shadow-sm overflow-hidden">

            {{-- Phase header --}}
            <div class="flex items-center gap-2 px-4 py-2.5 bg-base-200/50 border-b border-base-200">
                <button @click="phase._open = !phase._open" class="flex-1 flex items-center gap-2 text-left min-w-0 py-0.5">
                    <svg :class="phase._open ? 'rotate-90' : ''" class="w-3.5 h-3.5 text-base-content/30 shrink-0 transition-transform duration-200"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="m9 18 6-6-6-6"/>
                    </svg>
                    <span class="text-xs font-bold text-base-content/40 uppercase tracking-wide shrink-0" x-text="'P' + (pIdx+1)"></span>
                    <span class="font-semibold text-sm truncate" x-text="phase.label"></span>
                    <code class="text-xs text-base-content/35 shrink-0" x-text="phase.key"></code>
                    <span x-show="phase.is_initial" class="badge badge-info badge-xs shrink-0">Khởi tạo</span>
                    <span x-show="phase.auto_assign_data_collection" class="badge badge-secondary badge-xs shrink-0">Auto khảo sát</span>
                    <span class="badge badge-ghost badge-xs shrink-0 tabular-nums"
                          x-text="phase.checklist_items.length + ' mục'"></span>
                </button>

                <div class="flex items-center gap-0.5 shrink-0">
                    <button @click="movePhaseUp(pIdx)" :disabled="pIdx===0||saving" class="btn btn-ghost btn-xs btn-circle opacity-50 hover:opacity-100">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                    </button>
                    <button @click="movePhaseDown(pIdx)" :disabled="pIdx===phases.length-1||saving" class="btn btn-ghost btn-xs btn-circle opacity-50 hover:opacity-100">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="w-px h-3 bg-base-300 mx-0.5"></div>
                    <button @click="openPhaseModal(phase)" class="btn btn-ghost btn-xs btn-circle" title="Sửa phase">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button @click="deletePhase(phase, pIdx)" :disabled="saving" class="btn btn-ghost btn-xs btn-circle text-error/70 hover:text-error" title="Xóa phase">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>

            {{-- Phase body — checklist items --}}
            <div x-show="phase._open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="p-3 space-y-2">

                    <div x-show="phase.checklist_items.length === 0" class="text-center py-5 text-base-content/25 rounded-lg border border-dashed border-base-300">
                        <p class="text-xs">Chưa có mục checklist nào trong phase này</p>
                    </div>

                    <template x-for="(item, iIdx) in phase.checklist_items" :key="item.id">
                        <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg border border-base-200 bg-base-100">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline gap-1.5 flex-wrap">
                                    <span class="text-sm font-medium text-base-content truncate" x-text="item.label"></span>
                                    <span x-show="item.is_required" class="text-error text-xs font-bold shrink-0" title="Bắt buộc">●</span>
                                </div>
                                <code class="text-xs text-base-content/35" x-text="item.key"></code>
                            </div>
                            <div class="flex items-center gap-0.5 shrink-0">
                                <button @click="moveChecklistItemUp(phase, iIdx)" :disabled="iIdx===0||saving" class="btn btn-ghost btn-xs btn-circle opacity-50 hover:opacity-100">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                </button>
                                <button @click="moveChecklistItemDown(phase, iIdx)" :disabled="iIdx===phase.checklist_items.length-1||saving" class="btn btn-ghost btn-xs btn-circle opacity-50 hover:opacity-100">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <button @click="openChecklistItemModal(phase, item)" class="btn btn-ghost btn-xs btn-circle" title="Sửa">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button @click="deleteChecklistItem(phase, item, iIdx)" :disabled="saving" class="btn btn-ghost btn-xs btn-circle text-error/70 hover:text-error" title="Xóa">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>

                    <button @click="openChecklistItemModal(phase)" class="btn btn-ghost btn-xs gap-1.5 w-full justify-center border border-dashed border-base-300 mt-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Thêm mục checklist
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{--
        Phase modal — KHÔNG hardcode class "modal-open" tĩnh: DaisyUI 5 dùng
        `:root:has(.modal.modal-open)` để khoá scroll toàn trang khi có modal
        mở, chọn theo class trong DOM chứ không quan tâm `display:none` của
        x-show → nếu để "modal-open" cố định, trang bị khoá scroll vĩnh viễn
        dù modal chưa từng mở. Class phải bind động theo đúng trạng thái.
    --}}
    <div x-show="pModal.open" x-cloak class="modal" :class="{ 'modal-open': pModal.open }">
        <div class="modal-box max-w-md">
            <h3 class="font-bold text-base mb-4" x-text="pModal.id ? 'Sửa phase' : 'Thêm phase'"></h3>
            <div class="space-y-3">
                <div class="form-control">
                    <label class="label"><span class="label-text text-sm">Key (machine key — vd: draft, surveying)</span></label>
                    <input type="text" x-model="pModal.key" x-ref="pModalKeyInput" class="input input-bordered input-sm" placeholder="draft">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text text-sm">Nhãn hiển thị</span></label>
                    <input type="text" x-model="pModal.label" @keydown.enter="savePhase()" class="input input-bordered input-sm" placeholder="Khởi tạo">
                </div>
                <label class="label cursor-pointer justify-start gap-2.5">
                    <input type="checkbox" x-model="pModal.isInitial" class="checkbox checkbox-sm">
                    <span class="label-text text-sm">Phase khởi tạo mặc định (chỉ 1 phase)</span>
                </label>
                <label class="label cursor-pointer justify-start gap-2.5">
                    <input type="checkbox" x-model="pModal.autoAssign" class="checkbox checkbox-sm">
                    <span class="label-text text-sm">Tự động gán khảo sát thu thập dữ liệu khi vào phase này</span>
                </label>
            </div>
            <div class="modal-action">
                <button @click="pModal.open = false" class="btn btn-ghost btn-sm">Hủy</button>
                <button @click="savePhase()" :disabled="!pModal.key.trim() || !pModal.label.trim() || saving" class="btn btn-primary btn-sm">Lưu</button>
            </div>
        </div>
        <div class="modal-backdrop" @click="pModal.open = false"></div>
    </div>

    {{-- Checklist item modal — xem ghi chú ở phase modal về :class động --}}
    <div x-show="ciModal.open" x-cloak class="modal" :class="{ 'modal-open': ciModal.open }">
        <div class="modal-box max-w-md">
            <h3 class="font-bold text-base mb-4" x-text="ciModal.id ? 'Sửa mục checklist' : 'Thêm mục checklist'"></h3>
            <div class="space-y-3">
                <div class="form-control">
                    <label class="label"><span class="label-text text-sm">Key (machine key)</span></label>
                    <input type="text" x-model="ciModal.key" x-ref="ciModalKeyInput" class="input input-bordered input-sm" placeholder="field_survey_done">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text text-sm">Nhãn hiển thị</span></label>
                    <input type="text" x-model="ciModal.label" @keydown.enter="saveChecklistItem()" class="input input-bordered input-sm" placeholder="Hoàn thành khảo sát thực địa">
                </div>
                <label class="label cursor-pointer justify-start gap-2.5">
                    <input type="checkbox" x-model="ciModal.isRequired" class="checkbox checkbox-sm">
                    <span class="label-text text-sm">Bắt buộc hoàn thành</span>
                </label>
            </div>
            <div class="modal-action">
                <button @click="ciModal.open = false" class="btn btn-ghost btn-sm">Hủy</button>
                <button @click="saveChecklistItem()" :disabled="!ciModal.key.trim() || !ciModal.label.trim() || saving" class="btn btn-primary btn-sm">Lưu</button>
            </div>
        </div>
        <div class="modal-backdrop" @click="ciModal.open = false"></div>
    </div>

</div>

{{--
    Alpine component `verticalTemplateBuilder` đăng ký trong
    Modules/Deployment/resources/assets/js/deployment.js (alpine:init) —
    không inline script ở đây, xem docs/form-ui-spec.md §4.2.
    Trang include partial này phải @push('scripts') deployment.js + deployment.scss.
--}}
