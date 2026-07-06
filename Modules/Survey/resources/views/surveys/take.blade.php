@extends('layouts.backend')
@section('title', $survey->title)

@section('content')

@php
    $sectionCount = $sections->count();
    $isPreview    = $survey->status !== \Modules\Survey\Enums\SurveyStatus::Active;
@endphp

<div x-data="surveyTake({{ $sectionCount }})">

    {{-- Preview banner --}}
    @if($isPreview)
    <div class="alert alert-info py-2 px-4 mb-5 flex items-center gap-2 text-sm">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
        <span>Chế độ xem trước — khảo sát đang ở trạng thái <strong>{{ $survey->status->label() }}</strong>, chưa mở cho người dùng.</span>
    </div>
    @endif

    {{-- Page header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">{{ $survey->title }}</h1>
            @if($survey->description)
            <p class="text-sm text-base-content/50 mt-0.5">{{ $survey->description }}</p>
            @endif
        </div>
        <a href="{{ route('backend.surveys.my') }}" class="btn btn-ghost btn-sm gap-1.5 shrink-0 ml-4">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Quay lại
        </a>
    </div>

    {{-- Already done --}}
    @if($alreadyDone)
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body items-center text-center py-12">
            <div class="w-16 h-16 rounded-full bg-info/10 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-base-content mb-1">Đã hoàn thành</h2>
            <p class="text-sm text-base-content/50">Bạn đã hoàn thành khảo sát này rồi.</p>
        </div>
    </div>

    @else

    {{-- Progress bar (multi-section only) --}}
    @if($sectionCount > 1)
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
        <div class="card-body p-4">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-xs text-base-content/50">
                    Phần
                    <span x-text="currentSection + 1" class="font-semibold text-base-content"></span>
                    / {{ $sectionCount }}
                </span>
                <span class="text-xs font-medium text-primary" x-text="progress() + '%'"></span>
            </div>
            <progress class="progress progress-primary w-full h-1.5" :value="progress()" max="100"></progress>
        </div>
    </div>
    @endif

    {{-- Error banner --}}
    @if($errors->any())
    <div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="font-semibold">Có {{ $errors->count() }} câu trả lời cần kiểm tra lại:</p>
            <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Form --}}
    <form method="POST"
          action="{{ route('backend.surveys.take.submit', $survey->slug) }}"
          novalidate
          data-survey-take-form
          @submit.prevent="handleSubmit($event)">
        @csrf

        @forelse($sections as $sectionIndex => $section)
        <div x-show="currentSection === {{ $sectionIndex }}"
             data-section="{{ $sectionIndex }}">

            <div class="card bg-base-100 shadow-sm border border-base-200">

                {{-- Section header --}}
                @if($section->title || $sectionCount > 1)
                <div class="border-b border-base-200 px-6 py-4 flex items-center gap-3">
                    @if($sectionCount > 1)
                    <span class="badge badge-primary badge-outline text-xs shrink-0">
                        Phần {{ $sectionIndex + 1 }}
                    </span>
                    @endif
                    @if($section->title)
                    <h2 class="font-semibold text-base-content">{{ $section->title }}</h2>
                    @endif
                </div>
                @endif

                {{-- Questions --}}
                <div class="p-6 divide-y divide-base-200">

                    @foreach($section->fields as $fieldIndex => $field)
                    @php
                        $fieldKey  = $field->field_key;
                        $inputName = "answers[{$fieldKey}]";
                        $oldVal    = old("answers.{$fieldKey}");
                        $hasError  = $errors->has($fieldKey);
                    @endphp

                    <div class="survey-question py-5 first:pt-0 last:pb-0"
                         id="field-{{ $fieldKey }}"
                         @if($field->is_required) data-required="1" @endif>

                        {{-- Question label row --}}
                        <div class="flex items-start gap-3 mb-3">
                            <span class="text-xs font-mono text-base-content/30 mt-0.5 shrink-0 w-6 text-right leading-5">
                                {{ $fieldIndex + 1 }}.
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-base-content leading-relaxed">
                                    {{ $field->label }}
                                    @if($field->is_required)
                                    <span class="text-error ml-0.5">*</span>
                                    @endif
                                </p>
                                @if($field->placeholder)
                                <p class="text-xs text-base-content/40 mt-0.5">{{ $field->placeholder }}</p>
                                @endif
                                @error($fieldKey)
                                <p class="text-xs text-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Input area (indented to align with label text) --}}
                        <div class="pl-9">

                            {{-- ── 1. Text ── --}}
                            @if($field->field_type->value === 1)
                                <input type="text"
                                       name="{{ $inputName }}"
                                       value="{{ $oldVal }}"
                                       class="input input-bordered input-sm w-full @if($hasError) input-error @endif">

                            {{-- ── 2. Textarea ── --}}
                            @elseif($field->field_type->value === 2)
                                <textarea name="{{ $inputName }}"
                                          rows="4"
                                          class="textarea textarea-bordered textarea-sm w-full @if($hasError) textarea-error @endif">{{ $oldVal }}</textarea>

                            {{-- ── 3. Number ── --}}
                            @elseif($field->field_type->value === 3)
                                <input type="number"
                                       name="{{ $inputName }}"
                                       value="{{ $oldVal }}"
                                       class="input input-bordered input-sm w-40 @if($hasError) input-error @endif"
                                       @if($field->rule_min !== null) min="{{ $field->rule_min }}" @endif
                                       @if($field->rule_max !== null) max="{{ $field->rule_max }}" @endif>

                            {{-- ── 4. Select ── --}}
                            @elseif($field->field_type->value === 4)
                                <select name="{{ $inputName }}"
                                        id="ts-answers-{{ $fieldKey }}"
                                        class="select select-bordered select-sm w-full max-w-sm ts-init @if($hasError) select-error @endif"
                                        data-ts-placeholder="— Chọn —">
                                    <option value="">— Chọn —</option>
                                    @foreach($field->options as $opt)
                                    <option value="{{ $opt->option_value }}"
                                            @selected($oldVal === $opt->option_value)>
                                        {{ $opt->label }}
                                    </option>
                                    @endforeach
                                </select>

                            {{-- ── 5. Radio ── --}}
                            @elseif($field->field_type->value === 5)
                                <div class="flex flex-col gap-2">
                                    @foreach($field->options as $opt)
                                    <label class="survey-option-card flex items-center gap-3 px-4 py-2.5 rounded-lg border border-base-200 cursor-pointer hover:border-primary/50 has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                                        <input type="radio"
                                               name="{{ $inputName }}"
                                               value="{{ $opt->option_value }}"
                                               class="radio radio-primary radio-sm shrink-0"
                                               @checked($oldVal === $opt->option_value)>
                                        <span class="text-sm select-none flex-1">{{ $opt->label }}</span>
                                        @if($opt->is_other)
                                        <input type="text"
                                               name="answers_other[{{ $fieldKey }}]"
                                               placeholder="Nhập câu trả lời..."
                                               class="input input-bordered input-xs w-36"
                                               value="{{ old("answers_other.{$fieldKey}") }}"
                                               onclick="this.closest('label').querySelector('[type=radio]').checked=true">
                                        @endif
                                    </label>
                                    @endforeach
                                </div>

                            {{-- ── 6. Checkbox ── --}}
                            @elseif($field->field_type->value === 6)
                                <div class="flex flex-col gap-2">
                                    @foreach($field->options as $opt)
                                    @php $checked = is_array($oldVal) && in_array($opt->option_value, $oldVal); @endphp
                                    <label class="survey-option-card flex items-center gap-3 px-4 py-2.5 rounded-lg border border-base-200 cursor-pointer hover:border-primary/50 has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                                        <input type="checkbox"
                                               name="{{ $inputName }}[]"
                                               value="{{ $opt->option_value }}"
                                               class="checkbox checkbox-primary checkbox-sm shrink-0"
                                               @checked($checked)>
                                        <span class="text-sm select-none flex-1">{{ $opt->label }}</span>
                                        @if($opt->is_other)
                                        <input type="text"
                                               name="answers_other[{{ $fieldKey }}]"
                                               placeholder="Nhập câu trả lời..."
                                               class="input input-bordered input-xs w-36"
                                               value="{{ old("answers_other.{$fieldKey}") }}"
                                               onclick="this.closest('label').querySelector('[type=checkbox]').checked=true">
                                        @endif
                                    </label>
                                    @endforeach
                                </div>
                                @if($field->rule_max_select)
                                <p class="text-xs text-base-content/40 mt-2">Chọn tối đa {{ $field->rule_max_select }} lựa chọn</p>
                                @endif

                            {{-- ── 7. Rating (star) ── --}}
                            @elseif($field->field_type->value === 7)
                                @php $maxStars = $field->rule_max ?? 5; @endphp
                                <div x-data="{ rating: {{ (int)($oldVal ?? 0) }} }" class="flex items-center gap-0.5">
                                    <input type="hidden" name="{{ $inputName }}" :value="rating"
                                           @if($field->is_required) data-required-value @endif>
                                    @for($s = 1; $s <= $maxStars; $s++)
                                    <button type="button"
                                            @click="rating = (rating === {{ $s }} ? 0 : {{ $s }})"
                                            class="survey-star p-0.5 hover:scale-110 active:scale-95 transition-transform">
                                        <svg class="w-7 h-7 transition-colors"
                                             :class="rating >= {{ $s }} ? 'text-yellow-400' : 'text-base-content/20'"
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </button>
                                    @endfor
                                    <span class="text-xs text-base-content/40 ml-2 tabular-nums"
                                          x-text="rating > 0 ? rating + '/{{ $maxStars }}' : 'Chưa chọn'"></span>
                                </div>

                            {{-- ── 8. Date (flatpickr) ── --}}
                            @elseif($field->field_type->value === 8)
                                <input type="text"
                                       name="{{ $inputName }}"
                                       value="{{ $oldVal }}"
                                       id="fp-survey-{{ $fieldKey }}"
                                       class="input input-bordered input-sm w-44 fp-init @if($hasError) input-error @endif"
                                       placeholder="DD/MM/YYYY">

                            {{-- ── 9. Boolean (Yes / No) ── --}}
                            @elseif($field->field_type->value === 9)
                                <div x-data="{ val: '{{ $oldVal ?? '' }}' }" class="flex gap-2">
                                    <input type="hidden" name="{{ $inputName }}" :value="val"
                                           @if($field->is_required) data-required-value @endif>
                                    <button type="button"
                                            @click="val = (val === '1' ? '' : '1')"
                                            :class="val === '1' ? 'btn-success' : 'btn-outline btn-success'"
                                            class="btn btn-sm gap-1.5 transition-all">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Có
                                    </button>
                                    <button type="button"
                                            @click="val = (val === '0' ? '' : '0')"
                                            :class="val === '0' ? 'btn-error' : 'btn-outline btn-error'"
                                            class="btn btn-sm gap-1.5 transition-all">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Không
                                    </button>
                                </div>

                            {{-- ── 10. Matrix ── --}}
                            @elseif($field->field_type->value === 10)
                                <div class="overflow-x-auto">
                                    <table class="table table-xs w-full border border-base-200 rounded-lg overflow-hidden">
                                        <thead class="bg-base-200/50">
                                            <tr>
                                                <th class="w-1/3 font-medium text-xs text-base-content/60"></th>
                                                @foreach($field->options as $opt)
                                                <th class="text-center text-xs font-medium text-base-content/70">{{ $opt->label }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($field->rows as $row)
                                            <tr class="hover:bg-base-50">
                                                <td class="text-sm">{{ $row->label }}</td>
                                                @foreach($field->options as $opt)
                                                <td class="text-center">
                                                    <input type="radio"
                                                           name="{{ $inputName }}[{{ $row->row_key }}]"
                                                           value="{{ $opt->option_value }}"
                                                           class="radio radio-primary radio-xs">
                                                </td>
                                                @endforeach
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                            {{-- ── 11. Ranking ── --}}
                            @elseif($field->field_type->value === 11)
                                <div class="flex flex-col gap-1.5">
                                    @foreach($field->options as $idx => $opt)
                                    <div class="flex items-center gap-3 bg-base-200/40 border border-base-200 rounded-lg px-3 py-2.5">
                                        <span class="text-xs font-mono text-base-content/30 w-5 text-right shrink-0">{{ $idx + 1 }}.</span>
                                        <span class="text-sm flex-1">{{ $opt->label }}</span>
                                        <select name="{{ $inputName }}[{{ $opt->option_value }}]"
                                                id="ts-ranking-{{ $fieldKey }}-{{ $idx }}"
                                                class="select select-bordered select-xs w-28 ts-init"
                                                data-ts-placeholder="— Vị trí —">
                                            <option value="">— Vị trí —</option>
                                            @for($r = 1; $r <= $field->options->count(); $r++)
                                            <option value="{{ $r }}">{{ $r }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    @endforeach
                                </div>

                            {{-- ── 12. NPS (0–10) ── --}}
                            @elseif($field->field_type->value === 12)
                                @php $npsOld = ($oldVal !== null && $oldVal !== '') ? (int)$oldVal : 'null'; @endphp
                                <div x-data="{ val: {{ $npsOld }} }">
                                    <input type="hidden" name="{{ $inputName }}" :value="val === null ? '' : val"
                                           @if($field->is_required) data-required-value @endif>
                                    <div class="flex flex-wrap gap-1.5 mt-0.5">
                                        @for($n = 0; $n <= 10; $n++)
                                        @php $colorClass = $n <= 6 ? 'btn-error' : ($n <= 8 ? 'btn-warning' : 'btn-success'); @endphp
                                        <button type="button"
                                                @click="val = (val === {{ $n }} ? null : {{ $n }})"
                                                :class="val === {{ $n }} ? '{{ $colorClass }}' : 'btn-outline'"
                                                class="btn btn-xs w-9 transition-all">{{ $n }}</button>
                                        @endfor
                                    </div>
                                    <div class="flex justify-between text-xs text-base-content/40 mt-1.5 px-0.5">
                                        <span>Hoàn toàn không</span>
                                        <span>Chắc chắn sẽ</span>
                                    </div>
                                </div>

                            {{-- ── fallback ── --}}
                            @else
                                <input type="text"
                                       name="{{ $inputName }}"
                                       value="{{ $oldVal }}"
                                       class="input input-bordered input-sm w-full">
                            @endif

                        </div>{{-- end input area --}}

                    </div>{{-- end survey-question --}}
                    @endforeach

                </div>{{-- end questions --}}

                {{-- Section footer: prev / next / submit --}}
                <div class="border-t border-base-200 px-6 py-4 flex items-center justify-between">

                    @if($sectionCount > 1)
                    <button type="button"
                            x-show="!isFirst()"
                            @click="prev()"
                            class="btn btn-ghost btn-sm gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Phần trước
                    </button>
                    <span x-show="isFirst()"></span>
                    @else
                    <span></span>
                    @endif

                    <div class="flex items-center gap-3">
                        @if($isPreview)
                        <span class="text-xs text-base-content/40">Chế độ xem trước — không thể nộp</span>
                        @endif

                        @if($sectionCount > 1)
                        <template x-if="!isLast()">
                            <button type="button" @click="next()" class="btn btn-primary btn-sm gap-1.5">
                                Tiếp theo
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </template>
                        <template x-if="isLast()">
                            <button type="submit"
                                    @if($isPreview) disabled @endif
                                    :disabled="submitting"
                                    class="btn btn-primary btn-sm gap-1.5">
                                <span x-show="submitting" class="loading loading-spinner loading-xs"></span>
                                <svg x-show="!submitting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                <span x-text="submitting ? 'Đang gửi...' : 'Nộp khảo sát'">Nộp khảo sát</span>
                            </button>
                        </template>
                        @else
                        <button type="submit"
                                @if($isPreview) disabled @endif
                                :disabled="submitting"
                                class="btn btn-primary btn-sm gap-1.5">
                            <span x-show="submitting" class="loading loading-spinner loading-xs"></span>
                            <svg x-show="!submitting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            <span x-text="submitting ? 'Đang gửi...' : 'Nộp khảo sát'">Nộp khảo sát</span>
                        </button>
                        @endif
                    </div>

                </div>{{-- end footer --}}

            </div>{{-- end card --}}

        </div>{{-- end section wrapper --}}
        @empty
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body items-center text-center py-10">
                <p class="text-sm text-base-content/50">Khảo sát này chưa có câu hỏi nào.</p>
            </div>
        </div>
        @endforelse

    </form>

    @endif{{-- end !$alreadyDone --}}

</div>

@push('styles')
    @vite(['Modules/Survey/resources/assets/sass/survey.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'Modules/Survey/resources/assets/js/survey.js',
    ], 'build/backend')
@endpush

@endsection
