{{--
    Survey Builder — Alpine.js component
    Nhận: $survey, $sectionsData (array), $fieldTypes (array), $isLocked (bool)
--}}

<div
    x-data="surveyBuilder(@js($sectionsData), @js($fieldTypes), @js($survey->id), @js($isLocked), @js(csrf_token()))"
    class="space-y-3"
>
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
            <h2 class="font-bold text-base text-base-content">Cấu trúc khảo sát</h2>
            <span class="badge badge-ghost badge-sm"
                  x-text="sections.reduce((n,s) => n + s.fields.filter(f=>f.is_active).length, 0) + ' fields active'"></span>
        </div>
        <div class="flex gap-2 items-center">
            <span x-show="isLocked" class="badge badge-warning badge-sm">
                🔒 Đã có responses
            </span>
            <span x-show="saving" class="loading loading-spinner loading-xs text-primary"></span>
            <button @click="openSectionModal()" :disabled="saving"
                    class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Thêm section
            </button>
        </div>
    </div>

    {{-- Empty state --}}
    <div x-show="sections.length === 0"
         class="text-center py-16 border-2 border-dashed border-base-300 rounded-2xl bg-base-100">
        <svg class="w-10 h-10 mx-auto mb-3 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <p class="font-semibold text-base-content/40">Chưa có section nào</p>
        <p class="text-xs text-base-content/30 mt-1">Nhấn "Thêm section" để bắt đầu</p>
    </div>

    {{-- Section list --}}
    <template x-for="(section, sIdx) in sections" :key="section.id">
        <div class="rounded-xl border border-base-200 bg-base-100 shadow-sm overflow-hidden">

            {{-- Section header --}}
            <div class="flex items-center gap-2 px-4 py-2.5 bg-base-200/50 border-b border-base-200">
                <button @click="section._open = !section._open"
                        class="flex-1 flex items-center gap-2 text-left min-w-0 py-0.5">
                    <svg :class="section._open ? 'rotate-90' : ''"
                         class="w-3.5 h-3.5 text-base-content/30 shrink-0 transition-transform duration-200"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="m9 18 6-6-6-6"/>
                    </svg>
                    <span class="text-xs font-bold text-base-content/40 uppercase tracking-wide shrink-0"
                          x-text="'S' + (sIdx+1)"></span>
                    <span class="font-semibold text-sm truncate" x-text="section.title"></span>
                    <span class="badge badge-ghost badge-xs shrink-0 tabular-nums"
                          x-text="section.fields.length + ' field' + (section.fields.length !== 1 ? 's' : '')"></span>
                </button>

                <div class="flex items-center gap-0.5 shrink-0">
                    <button @click="moveSectionUp(sIdx)" :disabled="sIdx===0||saving"
                            class="btn btn-ghost btn-xs btn-circle opacity-50 hover:opacity-100">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                    </button>
                    <button @click="moveSectionDown(sIdx)" :disabled="sIdx===sections.length-1||saving"
                            class="btn btn-ghost btn-xs btn-circle opacity-50 hover:opacity-100">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="w-px h-3 bg-base-300 mx-0.5"></div>
                    <button @click="openSectionModal(section)"
                            class="btn btn-ghost btn-xs btn-circle" title="Đổi tên section">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button @click="deleteSection(section, sIdx)" :disabled="saving||isLocked"
                            class="btn btn-ghost btn-xs btn-circle text-error/70 hover:text-error" title="Xóa section">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>

            {{-- Section body --}}
            <div x-show="section._open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="p-3 space-y-2">

                    {{-- Empty section state --}}
                    <div x-show="section.fields.length === 0"
                         class="text-center py-5 text-base-content/25 rounded-lg border border-dashed border-base-300">
                        <p class="text-xs">Chưa có field nào trong section này</p>
                    </div>

                    {{-- Field list --}}
                    <template x-for="(field, fIdx) in section.fields" :key="field.id">
                        <div class="rounded-lg border border-base-200 border-l-[3px] overflow-hidden transition-opacity"
                             :class="field.is_active ? 'bg-base-100' : 'bg-base-200/40 opacity-60'"
                             :style="`border-left-color: ${typeColor(field.field_type)}`">

                            {{-- Field main row --}}
                            <div class="flex items-start gap-2.5 px-3 py-2.5">

                                {{-- Type chip --}}
                                <div class="pt-0.5 shrink-0">
                                    <span class="badge badge-xs badge-soft"
                                          :class="typeBadgeClass(field.field_type)"
                                          x-text="getTypeLabel(field.field_type)"></span>
                                </div>

                                {{-- Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-baseline gap-1.5 flex-wrap">
                                        <span class="text-sm font-semibold text-base-content truncate"
                                              x-text="field.label"></span>
                                        <span x-show="field.is_required"
                                              class="text-error text-xs font-bold shrink-0" title="Bắt buộc">●</span>
                                        <span x-show="!field.is_active"
                                              class="badge badge-error badge-xs shrink-0">off</span>
                                    </div>
                                    {{-- Key — click to copy --}}
                                    <button type="button"
                                            @click="copyKey(field.field_key)"
                                            class="flex items-center gap-1 group/key mt-0.5 text-left"
                                            title="Click để copy key">
                                        <code class="text-xs font-mono text-base-content/35 group-hover/key:text-base-content/70 transition-colors"
                                              x-text="field.field_key"></code>
                                        <svg class="w-2.5 h-2.5 text-base-content/20 group-hover/key:text-primary/60 transition-colors shrink-0"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                    {{-- Option count chip --}}
                                    <span x-show="isChoiceType(field.field_type)"
                                          class="inline-flex items-center text-xs text-base-content/35 mt-0.5 gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h7"/></svg>
                                        <span x-text="field.options.length + ' lựa chọn'"></span>
                                    </span>
                                </div>

                                {{-- Actions --}}
                                <div class="flex items-center gap-0.5 shrink-0 pt-0.5">
                                    {{-- Reorder (subtle) --}}
                                    <div class="flex items-center gap-0.5">
                                        <button @click="moveFieldUp(section, fIdx)" :disabled="fIdx===0||saving"
                                                class="btn btn-ghost btn-xs btn-circle opacity-30 hover:opacity-100 disabled:opacity-15">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                        </button>
                                        <button @click="moveFieldDown(section, fIdx)" :disabled="fIdx===section.fields.length-1||saving"
                                                class="btn btn-ghost btn-xs btn-circle opacity-30 hover:opacity-100 disabled:opacity-15">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </div>

                                    <div class="w-px h-3.5 bg-base-300 mx-0.5"></div>

                                    {{-- Edit --}}
                                    <button @click="openFieldModal(section, field)"
                                            class="btn btn-ghost btn-xs btn-circle" title="Chỉnh sửa field">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    {{-- Toggle active --}}
                                    <button @click="toggleField(section, field)" :disabled="saving"
                                            :class="field.is_active ? 'text-warning/70 hover:text-warning' : 'text-success/70 hover:text-success'"
                                            class="btn btn-ghost btn-xs btn-circle"
                                            :title="field.is_active ? 'Deactivate field' : 'Activate field'">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path x-show="field.is_active" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            <path x-show="!field.is_active" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>

                                    {{-- Delete (locked survey hides) --}}
                                    <button x-show="!isLocked"
                                            @click="deleteField(section, field, fIdx)" :disabled="saving"
                                            class="btn btn-ghost btn-xs btn-circle text-error/50 hover:text-error" title="Xóa field">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Inline options panel (choice types) --}}
                            <div x-show="isChoiceType(field.field_type)"
                                 class="border-t border-base-200 bg-base-200/30 px-3 py-2">

                                {{-- Options list --}}
                                <div x-show="field.options.length > 0" class="space-y-0.5 mb-2">
                                    <template x-for="(opt, oIdx) in field.options" :key="opt.id">
                                        <div class="flex items-center gap-2 rounded-md px-1.5 py-1 hover:bg-base-200/60 transition-colors">
                                            {{-- Reorder --}}
                                            <div class="flex flex-col gap-0 shrink-0">
                                                <button @click="moveOptionUp(field, oIdx)" :disabled="oIdx===0||saving"
                                                        class="p-0 h-3.5 w-3.5 flex items-center justify-center text-base-content/30 hover:text-base-content/70 disabled:opacity-20 disabled:cursor-not-allowed">
                                                    <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>
                                                </button>
                                                <button @click="moveOptionDown(field, oIdx)" :disabled="oIdx===field.options.length-1||saving"
                                                        class="p-0 h-3.5 w-3.5 flex items-center justify-center text-base-content/30 hover:text-base-content/70 disabled:opacity-20 disabled:cursor-not-allowed">
                                                    <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                            </div>
                                            {{-- Number --}}
                                            <span class="text-xs text-base-content/30 w-4 text-right shrink-0 tabular-nums"
                                                  x-text="oIdx + 1"></span>
                                            {{-- Value chip --}}
                                            <code class="text-xs font-mono bg-base-200 text-base-content/50 px-1.5 py-0.5 rounded shrink-0 max-w-[90px] truncate"
                                                  x-text="opt.option_value"></code>
                                            {{-- Label --}}
                                            <span class="text-sm flex-1 truncate text-base-content" x-text="opt.label"></span>
                                            {{-- Other badge --}}
                                            <span x-show="opt.is_other" class="badge badge-neutral badge-xs shrink-0">other</span>
                                            {{-- Actions — luôn hiển thị --}}
                                            <div class="flex items-center gap-0.5 shrink-0">
                                                <button @click="openOptionEdit(field, opt)"
                                                        title="Sửa lựa chọn"
                                                        class="btn btn-ghost btn-xs btn-circle text-base-content/40 hover:text-primary">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button @click="deleteOption(field, opt, oIdx)"
                                                        :disabled="saving || isLocked"
                                                        title="Xóa lựa chọn"
                                                        class="btn btn-ghost btn-xs btn-circle text-base-content/40 hover:text-error disabled:opacity-30">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                {{-- Add / edit option inline form --}}
                                <div x-show="optForm.fieldId === field.id" x-cloak>
                                    <div class="flex gap-2 flex-wrap items-end bg-base-100 rounded-lg p-2.5 border border-base-300 shadow-sm">
                                        <div class="flex flex-col gap-1 shrink-0">
                                            <span class="text-xs font-medium text-base-content/50">Value key</span>
                                            <input type="text" x-model="optForm.optionValue"
                                                   @input="if(!optForm.labelEdited) optForm.label = optForm.optionValue"
                                                   :readonly="optForm.id && isLocked"
                                                   class="input input-xs font-mono w-28"
                                                   placeholder="vd: chatgpt"
                                                   @keydown.enter="saveOption(field)">
                                        </div>
                                        <div class="flex flex-col gap-1 flex-1 min-w-[120px]">
                                            <span class="text-xs font-medium text-base-content/50">Nhãn hiển thị</span>
                                            <input type="text" x-model="optForm.label"
                                                   @input="optForm.labelEdited = true"
                                                   class="input input-xs w-full"
                                                   placeholder="vd: ChatGPT"
                                                   @keydown.enter="saveOption(field)">
                                        </div>
                                        <label class="flex items-center gap-1.5 text-xs cursor-pointer self-end pb-1">
                                            <input type="checkbox" x-model="optForm.isOther" class="checkbox checkbox-xs checkbox-neutral">
                                            <span class="text-base-content/60">Mục khác</span>
                                        </label>
                                        <div class="flex gap-1.5 self-end">
                                            <button @click="saveOption(field)" :disabled="saving||!optForm.optionValue.trim()||!optForm.label.trim()"
                                                    class="btn btn-primary btn-xs">
                                                <span x-text="optForm.id ? 'Lưu' : 'Thêm'"></span>
                                            </button>
                                            <button @click="closeOptionForm()" class="btn btn-ghost btn-xs">Hủy</button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Add option button --}}
                                <button x-show="optForm.fieldId !== field.id"
                                        @click="openOptionAdd(field)"
                                        class="mt-1 flex items-center gap-1.5 text-xs text-base-content/40 hover:text-primary transition-colors px-1 py-0.5 rounded">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                    Thêm lựa chọn
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- Add field button --}}
                    <button @click="openFieldModal(section)"
                            class="group w-full flex items-center justify-center gap-2 border-2 border-dashed border-base-300 hover:border-primary rounded-lg py-3 text-sm text-base-content/35 hover:text-primary transition-all duration-150">
                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Thêm field vào section này
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ─────────────────────── MODAL: Section ─────────────────────── --}}
    <dialog class="modal" :class="{ 'modal-open': sModal.open }">
        <div class="modal-box max-w-sm">
            <h3 class="font-bold text-lg mb-5" x-text="sModal.id ? 'Đổi tên section' : 'Thêm section mới'"></h3>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">
                    Tiêu đề section <span class="text-error">*</span>
                </legend>
                <input type="text" x-model="sModal.title"
                       class="input w-full"
                       placeholder="VD: Thông tin cá nhân"
                       @keydown.enter="saveSection()"
                       x-ref="sModalInput">
            </fieldset>

            <div class="flex justify-end gap-2 mt-6">
                <button @click="sModal.open = false" class="btn btn-ghost btn-sm">Hủy</button>
                <button @click="saveSection()" :disabled="saving || !sModal.title.trim()"
                        class="btn btn-primary btn-sm min-w-[80px]">
                    <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                    <span x-show="!saving" x-text="sModal.id ? 'Lưu' : 'Tạo section'"></span>
                </button>
            </div>
        </div>
        <div class="modal-backdrop" @click="sModal.open = false"><button>close</button></div>
    </dialog>

    {{-- ─────────────────────── MODAL: Field ──────────────────────── --}}
    <dialog class="modal" :class="{ 'modal-open': fModal.open }">
        <div class="modal-box w-full max-w-lg">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-bold text-lg" x-text="fModal.id ? 'Chỉnh sửa field' : 'Thêm field mới'"></h3>
                {{-- Field key chip (khi edit) --}}
                <button x-show="fModal.id"
                        type="button"
                        @click="copyKey(fModal.fieldKey)"
                        class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-base-200 hover:bg-base-300 transition-colors group/chip"
                        title="Click để copy key">
                    <svg class="w-3 h-3 text-base-content/40 group-hover/chip:text-primary transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    <code class="text-xs font-mono text-base-content/60 group-hover/chip:text-base-content transition-colors"
                          x-text="fModal.fieldKey"></code>
                    <svg class="w-3 h-3 text-base-content/30 group-hover/chip:text-primary/70 transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </button>
                {{-- Key preview (khi thêm mới) --}}
                <div x-show="!fModal.id"
                     class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-base-200">
                    <svg class="w-3 h-3 text-base-content/30 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <code class="text-xs font-mono text-base-content/40" x-text="previewKey"></code>
                    <span class="text-xs text-base-content/25">auto</span>
                </div>
            </div>

            <div class="space-y-4">
                {{-- Label --}}
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">
                        Nhãn hiển thị <span class="text-error">*</span>
                    </legend>
                    <input type="text" x-model="fModal.label"
                           class="input input-sm w-full"
                           placeholder="VD: Bạn hay dùng công cụ AI nào?"
                           x-ref="fModalLabelInput">
                </fieldset>

                {{-- Field type + Required (same row) --}}
                <div class="grid grid-cols-2 gap-3 items-end">
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">
                            Loại field <span class="text-error">*</span>
                        </legend>
                        <select x-model.number="fModal.fieldType"
                                @change="previewKey = mkPreviewKey(fModal.fieldType)"
                                :disabled="fModal.id && isLocked"
                                class="select select-sm w-full">
                            <template x-for="ft in fieldTypes" :key="ft.value">
                                <option :value="ft.value" x-text="ft.label"></option>
                            </template>
                        </select>
                    </fieldset>

                    <label class="flex items-center gap-2 cursor-pointer pb-1.5">
                        <input type="checkbox" x-model="fModal.isRequired" class="checkbox checkbox-sm checkbox-primary">
                        <span class="text-sm font-medium">Bắt buộc</span>
                    </label>
                </div>

                {{-- Rules: text/textarea/number/rating --}}
                <div x-show="hasRulesType(fModal.fieldType)" class="grid grid-cols-2 gap-3">
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Giá trị tối thiểu</legend>
                        <input type="number" x-model.number="fModal.ruleMin" class="input input-sm w-full" placeholder="Không giới hạn">
                    </fieldset>
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Giá trị tối đa</legend>
                        <input type="number" x-model.number="fModal.ruleMax" class="input input-sm w-full" placeholder="Không giới hạn">
                    </fieldset>
                </div>

                {{-- Max select (checkbox) --}}
                <fieldset x-show="fModal.fieldType === 6" class="fieldset">
                    <legend class="fieldset-legend">Tối đa số lựa chọn</legend>
                    <input type="number" x-model.number="fModal.ruleMaxSelect"
                           class="input input-sm w-32" min="1" placeholder="Không giới hạn">
                </fieldset>

                {{-- Placeholder (text/textarea) --}}
                <fieldset x-show="fModal.fieldType === 1 || fModal.fieldType === 2" class="fieldset">
                    <legend class="fieldset-legend">Placeholder</legend>
                    <input type="text" x-model="fModal.placeholder"
                           class="input input-sm w-full" placeholder="Gợi ý cho người điền...">
                </fieldset>

                {{-- Info for choice fields --}}
                <div x-show="isChoiceType(fModal.fieldType)"
                     role="alert" class="alert alert-info alert-soft py-2.5 text-xs gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Sau khi lưu, thêm các lựa chọn trực tiếp trên card field trong builder.
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button @click="fModal.open = false" class="btn btn-ghost btn-sm">Hủy</button>
                <button @click="saveField()" :disabled="saving || !fModal.label.trim()"
                        class="btn btn-primary btn-sm min-w-[100px]">
                    <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                    <span x-show="!saving" x-text="fModal.id ? 'Lưu thay đổi' : 'Thêm field'"></span>
                </button>
            </div>
        </div>
        <div class="modal-backdrop" @click="fModal.open = false"><button>close</button></div>
    </dialog>
</div>

@push('scripts')
<script>
function surveyBuilder(sectionsData, fieldTypes, surveyId, isLocked, csrfToken) {
    return {
        sections: sectionsData.map(s => ({ ...s, _open: true })),
        fieldTypes,
        surveyId,
        isLocked,
        csrfToken,
        saving: false,
        flash: { text: '', type: 'success' },

        // ── Section modal ──────────────────────────────────────────────
        sModal: { open: false, id: null, title: '' },

        openSectionModal(section = null) {
            this.sModal = { open: true, id: section?.id ?? null, title: section?.title ?? '' };
            this.$nextTick(() => this.$refs.sModalInput?.focus());
        },

        async saveSection() {
            if (!this.sModal.title.trim()) return;
            const url    = this.sModal.id
                ? `/dashboard/surveys/${this.surveyId}/sections/${this.sModal.id}`
                : `/dashboard/surveys/${this.surveyId}/sections`;
            const method = this.sModal.id ? 'PUT' : 'POST';
            const res    = await this.api(url, method, { title: this.sModal.title });
            if (!res) return;
            if (this.sModal.id) {
                const s = this.sections.find(s => s.id === this.sModal.id);
                if (s) s.title = res.data.title;
            } else {
                this.sections.push({ ...res.data, fields: [], _open: true });
            }
            this.sModal.open = false;
            this.dispatchStats();
            this.ok(res.message);
        },

        async deleteSection(section, idx) {
            if (!confirm(`Xóa section "${section.title}"?\n\nTất cả field bên trong cũng sẽ bị xóa. Hành động không thể hoàn tác.`)) return;
            const res = await this.api(`/dashboard/surveys/${this.surveyId}/sections/${section.id}`, 'DELETE');
            if (!res) return;
            this.sections.splice(idx, 1);
            this.dispatchStats();
            this.ok(res.message);
        },

        async moveSectionUp(idx) {
            if (idx === 0) return;
            [this.sections[idx - 1], this.sections[idx]] = [this.sections[idx], this.sections[idx - 1]];
            await this.reorder('sections', this.sections);
        },

        async moveSectionDown(idx) {
            if (idx === this.sections.length - 1) return;
            [this.sections[idx + 1], this.sections[idx]] = [this.sections[idx], this.sections[idx + 1]];
            await this.reorder('sections', this.sections);
        },

        // ── Field modal ────────────────────────────────────────────────
        fModal: {
            open: false, id: null, sectionId: null, fieldKey: '',
            label: '', fieldType: 1, isRequired: false,
            ruleMin: null, ruleMax: null, ruleMaxSelect: null, placeholder: '',
        },
        previewKey: 'txt_a3b2c8f1',

        openFieldModal(section, field = null) {
            const type = field?.field_type ?? 1;
            this.fModal = {
                open:          true,
                id:            field?.id ?? null,
                sectionId:     section.id,
                fieldKey:      field?.field_key ?? '',
                label:         field?.label ?? '',
                fieldType:     type,
                isRequired:    field?.is_required ?? false,
                ruleMin:       field?.rule_min ?? null,
                ruleMax:       field?.rule_max ?? null,
                ruleMaxSelect: field?.rule_max_select ?? null,
                placeholder:   field?.placeholder ?? '',
            };
            if (!field) this.previewKey = this.mkPreviewKey(type);
            this.$nextTick(() => this.$refs.fModalLabelInput?.focus());
        },

        async saveField() {
            if (!this.fModal.label.trim()) return;
            const url    = this.fModal.id
                ? `/dashboard/surveys/${this.surveyId}/fields/${this.fModal.id}`
                : `/dashboard/surveys/${this.surveyId}/fields`;
            const method = this.fModal.id ? 'PUT' : 'POST';
            const body   = {
                section_id:      this.fModal.sectionId,
                label:           this.fModal.label,
                field_type:      this.fModal.fieldType,
                is_required:     this.fModal.isRequired,
                rule_min:        this.fModal.ruleMin   || null,
                rule_max:        this.fModal.ruleMax   || null,
                rule_max_select: this.fModal.ruleMaxSelect || null,
                placeholder:     this.fModal.placeholder  || null,
            };
            const res = await this.api(url, method, body);
            if (!res) return;
            const section = this.sections.find(s => s.id === this.fModal.sectionId);
            if (!section) return;
            if (this.fModal.id) {
                const idx = section.fields.findIndex(f => f.id === this.fModal.id);
                if (idx >= 0) section.fields[idx] = res.data;
            } else {
                section.fields.push(res.data);
            }
            this.fModal.open = false;
            this.dispatchStats();
            this.ok(res.message);
        },

        async toggleField(section, field) {
            const res = await this.api(`/dashboard/surveys/${this.surveyId}/fields/${field.id}/toggle`, 'PATCH');
            if (!res) return;
            field.is_active = res.is_active;
            this.dispatchStats();
            this.ok(res.message);
        },

        async deleteField(section, field, idx) {
            if (!confirm(`Xóa field "${field.label}"?\n\n🔑 Key: ${field.field_key}\n\nHành động này không thể hoàn tác.`)) return;
            const res = await this.api(`/dashboard/surveys/${this.surveyId}/fields/${field.id}`, 'DELETE');
            if (!res) return;
            section.fields.splice(idx, 1);
            this.dispatchStats();
            this.ok(res.message);
        },

        async moveFieldUp(section, idx) {
            if (idx === 0) return;
            [section.fields[idx - 1], section.fields[idx]] = [section.fields[idx], section.fields[idx - 1]];
            await this.reorder('fields', section.fields);
        },

        async moveFieldDown(section, idx) {
            if (idx === section.fields.length - 1) return;
            [section.fields[idx + 1], section.fields[idx]] = [section.fields[idx], section.fields[idx + 1]];
            await this.reorder('fields', section.fields);
        },

        // ── Option inline form ─────────────────────────────────────────
        optForm: { fieldId: null, id: null, optionValue: '', label: '', isOther: false, labelEdited: false },

        openOptionAdd(field) {
            this.optForm = { fieldId: field.id, id: null, optionValue: '', label: '', isOther: false, labelEdited: false };
        },

        openOptionEdit(field, opt) {
            this.optForm = { fieldId: field.id, id: opt.id, optionValue: opt.option_value, label: opt.label, isOther: opt.is_other, labelEdited: true };
        },

        closeOptionForm() { this.optForm.fieldId = null; },

        async saveOption(field) {
            if (!this.optForm.optionValue.trim() || !this.optForm.label.trim()) return;
            const body = { option_value: this.optForm.optionValue, label: this.optForm.label, is_other: this.optForm.isOther };
            const url    = this.optForm.id
                ? `/dashboard/surveys/${this.surveyId}/fields/${field.id}/options/${this.optForm.id}`
                : `/dashboard/surveys/${this.surveyId}/fields/${field.id}/options`;
            const method = this.optForm.id ? 'PUT' : 'POST';
            const res    = await this.api(url, method, body);
            if (!res) return;
            if (this.optForm.id) {
                const idx = field.options.findIndex(o => o.id === this.optForm.id);
                if (idx >= 0) field.options[idx] = res.data;
            } else {
                field.options.push(res.data);
            }
            this.closeOptionForm();
            this.ok(res.message);
        },

        async deleteOption(field, opt, idx) {
            if (!confirm(`Xóa lựa chọn "${opt.label}"?`)) return;
            const res = await this.api(`/dashboard/surveys/${this.surveyId}/fields/${field.id}/options/${opt.id}`, 'DELETE');
            if (!res) return;
            field.options.splice(idx, 1);
            this.ok(res.message);
        },

        async moveOptionUp(field, idx) {
            if (idx === 0) return;
            [field.options[idx - 1], field.options[idx]] = [field.options[idx], field.options[idx - 1]];
            await this.reorder('options', field.options, field.id);
        },

        async moveOptionDown(field, idx) {
            if (idx === field.options.length - 1) return;
            [field.options[idx + 1], field.options[idx]] = [field.options[idx], field.options[idx + 1]];
            await this.reorder('options', field.options, field.id);
        },

        // ── Reorder ────────────────────────────────────────────────────
        async reorder(type, items, fieldId = null) {
            const payload = items.map((item, i) => ({ id: item.id, sort_order: i + 1 }));
            let url;
            if      (type === 'sections') url = `/dashboard/surveys/${this.surveyId}/sections/reorder`;
            else if (type === 'fields')   url = `/dashboard/surveys/${this.surveyId}/fields/reorder`;
            else                          url = `/dashboard/surveys/${this.surveyId}/fields/${fieldId}/options/reorder`;
            await this.api(url, 'PATCH', { items: payload });
        },

        // ── Helpers ────────────────────────────────────────────────────
        getTypeLabel(val) {
            return this.fieldTypes.find(t => t.value === val)?.label ?? '?';
        },

        isChoiceType(val)  { return [4, 5, 6].includes(val); },
        hasRulesType(val)  { return [1, 2, 3, 7].includes(val); },

        typeColor(type) {
            if ([1, 2].includes(type)) return 'var(--color-info)';
            if ([3, 7].includes(type)) return 'var(--color-success)';
            if ([4, 5, 6].includes(type)) return 'var(--color-secondary)';
            if (type === 8) return 'var(--color-warning)';
            return 'var(--color-neutral)';
        },

        typeBadgeClass(type) {
            if ([1, 2].includes(type)) return 'badge-info';
            if ([3, 7].includes(type)) return 'badge-success';
            if ([4, 5, 6].includes(type)) return 'badge-secondary';
            if (type === 8) return 'badge-warning';
            return 'badge-neutral';
        },

        // ── Key preview & copy ─────────────────────────────────────────
        TYPE_PREFIXES: { 1:'txt', 2:'ta', 3:'num', 4:'sel', 5:'rad', 6:'chk', 7:'rat', 8:'dt', 9:'bool' },

        mkPreviewKey(type) {
            const prefix = this.TYPE_PREFIXES[type] ?? 'f';
            const chars  = 'abcdefghijklmnopqrstuvwxyz0123456789';
            const rand   = Array.from({ length: 8 }, () => chars[Math.floor(Math.random() * chars.length)]).join('');
            return `${prefix}_${rand}`;
        },

        async copyKey(key) {
            try {
                await navigator.clipboard.writeText(key);
                this.ok(`Đã copy: ${key}`);
            } catch {
                this.ok(`Key: ${key}`);
            }
        },

        dispatchStats() {
            window.dispatchEvent(new CustomEvent('survey-stats-updated', {
                detail: {
                    sections:     this.sections.length,
                    activeFields: this.sections.reduce((n, s) => n + s.fields.filter(f => f.is_active).length, 0),
                },
            }));
        },

        ok(msg)  { this.flash = { text: msg, type: 'success' }; setTimeout(() => this.flash.text = '', 3000); },
        err(msg) { this.flash = { text: msg, type: 'error' };   setTimeout(() => this.flash.text = '', 5000); },

        async api(url, method, body = null) {
            this.saving = true;
            try {
                const opts = {
                    method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                };
                if (body && method !== 'GET') opts.body = JSON.stringify(body);
                const response = await fetch(url, opts);
                const json     = await response.json();
                if (!response.ok) {
                    const msg = json.message || (json.errors ? Object.values(json.errors).flat().join(' ') : 'Có lỗi xảy ra.');
                    this.err(msg);
                    return null;
                }
                return json;
            } catch {
                this.err('Lỗi kết nối. Vui lòng thử lại.');
                return null;
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endpush
