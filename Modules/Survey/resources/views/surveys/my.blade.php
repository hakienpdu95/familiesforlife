@extends('layouts.backend')
@section('title', 'Khảo sát của tôi')

@section('content')
<div class="max-w-3xl mx-auto py-6 px-4">

    <div class="mb-6">
        <h1 class="text-xl font-bold text-base-content">Khảo sát</h1>
        <p class="text-base-content/50 text-sm mt-0.5">Các khảo sát đang mở dành cho bạn</p>
    </div>

    @if($surveys->isEmpty())
    <div class="flex flex-col items-center justify-center py-20 text-center text-base-content/40">
        <svg class="w-12 h-12 mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p class="text-sm">Hiện chưa có khảo sát nào đang mở.</p>
    </div>
    @else
    <div class="space-y-3">
        @foreach($surveys as $survey)
        @php $done = in_array($survey->id, $doneIds); @endphp
        <div class="card bg-base-100 border border-base-200 hover:border-primary/30 transition-colors">
            <div class="card-body py-4 px-5 flex flex-row items-center gap-4">

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="font-medium text-base-content text-sm">{{ $survey->title }}</h2>
                        @if($done)
                        <span class="badge badge-success badge-xs gap-1">
                            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                            Đã hoàn thành
                        </span>
                        @endif
                    </div>
                    @if($survey->description)
                    <p class="text-xs text-base-content/50 mt-0.5 truncate">{{ $survey->description }}</p>
                    @endif
                </div>

                <div class="shrink-0">
                    @if($done && !$survey->allow_multiple_responses)
                    <span class="btn btn-xs btn-disabled">Đã nộp</span>
                    @else
                    <a href="{{ route('backend.surveys.take', $survey->slug) }}"
                       class="btn btn-primary btn-xs gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                        {{ $done ? 'Làm lại' : 'Làm khảo sát' }}
                    </a>
                    @endif
                </div>

            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
