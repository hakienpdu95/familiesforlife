@extends('layouts.backend')
@section('title', 'Đánh giá sẵn sàng — ' . $orgName)

@section('content')
<div class="max-w-3xl">

    <div class="mb-5">
        <h1 class="text-xl font-bold">Đánh giá sẵn sàng triển khai</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Tổ chức: <strong>{{ $orgName }}</strong></p>
    </div>

    @if($errors->any())
    <div class="alert alert-error mb-4">
        <ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST"
          action="{{ route('deployment.readiness.submit', ['vertical' => $vertical->code(), 'target' => $target->id]) }}">
        @csrf

        @foreach($sections as $section)
        <div class="card bg-base-100 border border-base-200 shadow-sm mb-5">
            <div class="card-body">
                <h2 class="card-title text-base">
                    {{ $section->icon ?? '' }} {{ $section->title }}
                    <span class="badge badge-outline badge-sm ml-auto">{{ $section->section_code }}</span>
                </h2>

                <div class="space-y-5 mt-2">
                    @foreach($section->fields as $field)
                    @php
                        $fieldType = $field->field_type->value;
                        $currentNum = $existingAnswers[$field->id] ?? null;
                        $currentStr = $existingStrings[$field->id] ?? null;
                    @endphp
                    <div>
                        <p class="text-sm font-medium mb-2">
                            {{ $loop->iteration }}. {{ $field->label }}
                            @if($field->is_required)
                            <span class="text-error text-xs ml-1">*</span>
                            @endif
                        </p>

                        {{-- Rating (1–5 stars) --}}
                        @if($fieldType === 7)
                        @php $maxRating = $field->rule_max ?? 5; @endphp
                        <div class="flex gap-1" x-data="{ rating: {{ (int)($currentNum ?? 0) }} }">
                            @for($r = 1; $r <= $maxRating; $r++)
                            <label class="cursor-pointer">
                                <input type="radio"
                                       name="answers[{{ $field->id }}]"
                                       value="{{ $r }}"
                                       class="sr-only"
                                       x-on:change="rating = {{ $r }}"
                                       {{ (int)($currentNum ?? 0) === $r ? 'checked' : '' }}>
                                <span x-on:click="rating = {{ $r }}"
                                      class="block w-10 h-10 flex items-center justify-center rounded-lg border-2 text-sm font-bold transition-all
                                             {{ (int)($currentNum ?? 0) >= $r ? 'bg-primary border-primary text-white' : 'border-base-300 text-base-content/40' }}"
                                      :class="rating >= {{ $r }} ? 'bg-primary border-primary text-white' : 'border-base-300 text-base-content/40'">
                                    {{ $r }}
                                </span>
                            </label>
                            @endfor
                            <span class="ml-3 self-center text-xs text-base-content/50"
                                  x-text="{{ json_encode(array_merge([''], array_fill(1, $maxRating, ''))) }}[rating] !== undefined ? (rating ? rating + '/{{ $maxRating }}' : 'Chưa chọn') : 'Chưa chọn'">
                                {{ $currentNum ? $currentNum . '/' . $maxRating : 'Chưa chọn' }}
                            </span>
                        </div>

                        {{-- NPS (0–10) --}}
                        @elseif($fieldType === 12)
                        <div x-data="{ val: {{ $currentNum !== null ? (int)$currentNum : 'null' }} }">
                            <div class="flex gap-1 flex-wrap">
                                @for($n = 0; $n <= 10; $n++)
                                <label class="cursor-pointer">
                                    <input type="radio"
                                           name="answers[{{ $field->id }}]"
                                           value="{{ $n }}"
                                           class="sr-only"
                                           x-on:change="val = {{ $n }}"
                                           {{ (int)($currentNum ?? -1) === $n ? 'checked' : '' }}>
                                    <span x-on:click="val = {{ $n }}"
                                          class="block w-9 h-9 flex items-center justify-center rounded-lg border-2 text-xs font-bold transition-all"
                                          :class="val === {{ $n }} ? 'bg-primary border-primary text-white' : 'border-base-300 text-base-content/40'">
                                        {{ $n }}
                                    </span>
                                </label>
                                @endfor
                            </div>
                            <div class="flex justify-between text-xs text-base-content/40 mt-1 px-1">
                                <span>Hoàn toàn không</span><span>Rất sẵn sàng</span>
                            </div>
                        </div>

                        {{-- Number --}}
                        @elseif($fieldType === 3)
                        <div class="flex items-center gap-2">
                            <input type="number"
                                   name="answers[{{ $field->id }}]"
                                   value="{{ $currentNum ?? '' }}"
                                   min="{{ $field->rule_min ?? 0 }}"
                                   max="{{ $field->rule_max ?? '' }}"
                                   placeholder="{{ $field->rule_min ?? 0 }}{{ $field->rule_max ? ' – ' . $field->rule_max : '' }}"
                                   class="input input-bordered input-sm w-32">
                            @if($field->rule_max)
                            <span class="text-xs text-base-content/40">/ {{ $field->rule_max }}</span>
                            @endif
                        </div>

                        {{-- Radio --}}
                        @elseif($fieldType === 5)
                        <div class="flex flex-col gap-2">
                            @foreach($field->options as $opt)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio"
                                       name="answers[{{ $field->id }}]"
                                       value="{{ $opt->option_value }}"
                                       class="radio radio-primary radio-sm"
                                       {{ ($currentStr === $opt->option_value) ? 'checked' : '' }}>
                                <span class="text-sm">{{ $opt->label }}</span>
                            </label>
                            @endforeach
                        </div>

                        {{-- Select --}}
                        @elseif($fieldType === 4)
                        <select name="answers[{{ $field->id }}]" class="select select-bordered select-sm w-full max-w-xs">
                            <option value="">— Chọn —</option>
                            @foreach($field->options as $opt)
                            <option value="{{ $opt->option_value }}"
                                    {{ $currentStr === $opt->option_value ? 'selected' : '' }}>
                                {{ $opt->label }}
                            </option>
                            @endforeach
                        </select>

                        {{-- Checkbox --}}
                        @elseif($fieldType === 6)
                        @php
                            // Single selected option comes back as string; multiple as array
                            $checkedValues = is_array($currentStr) ? $currentStr
                                : ($currentStr !== null ? [$currentStr] : []);
                        @endphp
                        <div class="flex flex-col gap-2">
                            @foreach($field->options as $opt)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox"
                                       name="answers_cb[{{ $field->id }}][]"
                                       value="{{ $opt->option_value }}"
                                       class="checkbox checkbox-primary checkbox-sm"
                                       {{ in_array($opt->option_value, $checkedValues) ? 'checked' : '' }}>
                                <span class="text-sm">{{ $opt->label }}</span>
                            </label>
                            @endforeach
                        </div>

                        {{-- Fallback: text --}}
                        @else
                        <input type="text"
                               name="answers[{{ $field->id }}]"
                               value="{{ $currentStr ?? '' }}"
                               class="input input-bordered input-sm w-full max-w-xs">
                        @endif

                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach

        <div class="flex gap-3 mt-2">
            <button type="submit" class="btn btn-primary">Nộp đánh giá</button>
            <a href="{{ route('deployment.targets.show', ['vertical' => $vertical->code(), 'target' => $target->id]) }}"
               class="btn btn-ghost">Hủy</a>
        </div>
    </form>
</div>
@endsection
