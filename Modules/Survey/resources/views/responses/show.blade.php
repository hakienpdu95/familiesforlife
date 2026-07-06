@extends('layouts.backend')

@section('title', 'Response #' . $response->id . ' — ' . $survey->title)


@section('content')
<div class="space-y-5 max-w-3xl">

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Response #{{ $response->id }}</h1>
            <p class="text-sm text-base-content/50 mt-0.5">{{ $survey->title }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('backend.surveys.responses.index', $survey) }}" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                Danh sách
            </a>

            @if($survey->hasScoring())
            <a href="{{ route('backend.surveys.responses.result.show', [$survey, $response]) }}"
               class="btn btn-info btn-soft btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 4h16v16H4z"/>
                </svg>
                Kết quả chấm điểm
            </a>
            @endif

            @can('survey.view_responses')
            <form method="POST"
                  action="{{ route('backend.surveys.responses.destroy', [$survey, $response]) }}"
                  onsubmit="return confirm('Xóa response #{{ $response->id }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-error btn-soft btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Xóa response
                </button>
            </form>
            @endcan
        </div>
    </div>

    {{-- Meta card --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-xs text-base-content/50 mb-0.5">Respondent</p>
                    <p class="font-medium">{{ $response->respondent_ref ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/50 mb-0.5">IP</p>
                    <p class="font-mono text-xs">{{ $response->respondent_ip ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/50 mb-0.5">Trạng thái</p>
                    <span class="badge badge-sm badge-soft {{ $response->status === \Modules\Survey\Enums\ResponseStatus::Complete ? 'badge-success' : 'badge-warning' }}">
                        {{ $response->status === \Modules\Survey\Enums\ResponseStatus::Complete ? 'Hoàn chỉnh' : 'Đang điền' }}
                    </span>
                </div>
                <div>
                    <p class="text-xs text-base-content/50 mb-0.5">Nộp lúc</p>
                    <p class="font-medium">{{ $response->submitted_at?->format('d/m/Y H:i') ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Sections + fields --}}
    @forelse($sections as $section)
    <div class="card bg-base-100 border border-base-200 shadow-sm">

        {{-- Section header --}}
        <div class="flex items-center gap-2 px-5 py-3 border-b border-base-200">
            @if($section['icon'])
            <span class="text-base">{{ $section['icon'] }}</span>
            @endif
            <h2 class="font-semibold text-base-content">{{ $section['title'] }}</h2>
        </div>

        <div class="divide-y divide-base-200">
            @forelse($section['fields'] as $field)
            <div class="px-5 py-3.5 flex gap-4">

                {{-- Field meta --}}
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="text-sm font-medium text-base-content">{{ $field['label'] }}</span>

                        @if(!$field['is_active'])
                        <span class="badge badge-xs badge-soft badge-neutral">Đã ẩn</span>
                        @endif

                        @if($field['is_required'])
                        <span class="badge badge-xs badge-soft badge-error">Bắt buộc</span>
                        @endif

                        <span class="badge badge-xs badge-soft badge-ghost font-mono text-base-content/40">
                            {{ $field['field_type']->label() }}
                        </span>
                    </div>

                    {{-- Answer value --}}
                    @if($field['is_answered'])
                        @if($field['is_multiple'] && is_array($field['display']))
                        <div class="flex flex-wrap gap-1.5 mt-1">
                            @foreach($field['display'] as $val)
                            <span class="badge badge-sm badge-soft badge-primary">{{ $val }}</span>
                            @endforeach
                        </div>
                        @elseif($field['field_type'] === \Modules\Survey\Enums\FieldType::Textarea)
                        <p class="text-sm text-base-content/80 mt-1 whitespace-pre-wrap bg-base-200/50 rounded-lg px-3 py-2">{{ $field['display'] }}</p>
                        @elseif($field['field_type'] === \Modules\Survey\Enums\FieldType::Rating)
                        <div class="flex items-center gap-1 mt-1">
                            @php $rating = (int)$field['display']; @endphp
                            @for($i = 1; $i <= 5; $i++)
                            <svg class="w-4 h-4 {{ $i <= $rating ? 'text-warning' : 'text-base-content/20' }}"
                                 fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            @endfor
                            <span class="text-sm text-base-content/60 ml-1">{{ $rating }}/5</span>
                        </div>
                        @elseif($field['field_type'] === \Modules\Survey\Enums\FieldType::Boolean)
                        <span class="badge badge-sm badge-soft {{ $field['display'] === 'Có' ? 'badge-success' : 'badge-error' }}">
                            {{ $field['display'] }}
                        </span>
                        @else
                        <p class="text-sm text-base-content/80 mt-0.5">{{ $field['display'] }}</p>
                        @endif
                    @else
                    <p class="text-sm text-base-content/30 italic mt-0.5">Chưa trả lời</p>
                    @endif
                </div>
            </div>
            @empty
            <div class="px-5 py-4 text-sm text-base-content/40">Section không có field nào.</div>
            @endforelse
        </div>
    </div>
    @empty
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body text-center text-base-content/40 py-10">Survey chưa có section nào.</div>
    </div>
    @endforelse

</div>
@endsection
