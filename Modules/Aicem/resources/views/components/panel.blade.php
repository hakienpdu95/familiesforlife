@php
    $statusUrl = $latestRun ? route('backend.aicem.generation.status', $latestRun) : null;
    $isInFlight = $latestRun && in_array($latestRun->status->value, ['pending', 'running'], true);
@endphp

<div class="card bg-base-100 shadow-sm border border-base-200"
     @if($isInFlight)
     x-data="{ polling: true }"
     x-init="
        const id = setInterval(() => {
            fetch('{{ $statusUrl }}', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => { if (data.status !== 'pending' && data.status !== 'running') { clearInterval(id); location.reload(); } });
        }, 4000);
     "
     @endif
>
    <div class="card-body">
        <div class="flex items-center gap-2 mb-1">
            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            <h2 class="card-title text-base">Trợ lý AI (AICEM)</h2>
        </div>

        @if($subjectTaxonomyPreview)
        <p class="text-xs text-base-content/40 mb-4">
            @foreach($subjectTaxonomyPreview as $key => $values)
                <span class="mr-2">{{ $key }}: {{ is_array($values) ? (count($values) ? implode(', ', $values) : '—') : $values }}</span>
            @endforeach
        </p>
        @endif

        @if(session('success'))
        <div class="alert alert-success py-2 px-3 mb-3 text-xs">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="alert alert-error py-2 px-3 mb-3 text-xs">{{ session('error') }}</div>
        @endif

        {{-- ── Chạy workflow ─────────────────────────────────────────────── --}}
        @if($workflows->isEmpty())
        <p class="text-sm text-base-content/40 py-3">Chưa có workflow AI nào khả dụng cho bài viết/sản phẩm này.</p>
        @else
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($workflows as $workflow)
            <form method="POST" action="{{ route('backend.aicem.generation.run') }}">
                @csrf
                <input type="hidden" name="subject_type" value="{{ $subjectType }}">
                <input type="hidden" name="subject_id" value="{{ $subjectId }}">
                <input type="hidden" name="workflow_id" value="{{ $workflow->id }}">
                <button type="submit" class="btn btn-primary btn-sm" {{ $isInFlight ? 'disabled' : '' }}>
                    {{ $workflow->name }}
                </button>
            </form>
            @endforeach
        </div>
        @endif

        {{-- ── Trạng thái run gần nhất ──────────────────────────────────── --}}
        @if($latestRun)
            @if($isInFlight)
            <div class="alert py-2 px-3 text-xs flex items-center gap-2">
                <span class="loading loading-spinner loading-xs"></span>
                Đang xử lý AI... (thường mất 5-30 giây, trang sẽ tự tải lại khi xong)
            </div>
            @elseif($latestRun->status->value === 'failed')
            <div class="alert alert-error py-2 px-3 text-xs">
                Chạy AI thất bại: {{ $latestRun->error_message ?? 'Lỗi không xác định.' }}
            </div>
            @elseif($latestRun->status->value === 'succeeded')
                @if($latestRun->error_message)
                <div class="alert alert-warning py-2 px-3 text-xs mb-3">{{ $latestRun->error_message }}</div>
                @endif

                {{-- filter() thay vì whereIn('status', [...]) — status là enum instance (SuggestionStatus),
                     không phải string, nên whereIn() so sánh với string literal sẽ luôn rỗng. --}}
                @php $pendingSuggestions = $latestRun->suggestions->filter(fn ($s) => in_array($s->status->value, ['pending', 'stale'], true)); @endphp

                @if($pendingSuggestions->isEmpty())
                <p class="text-sm text-base-content/40 py-2">Không có đề xuất nào đang chờ quyết định.</p>
                @else
                <div class="space-y-3">
                    @foreach($pendingSuggestions as $suggestion)
                    <div class="border border-base-200 rounded-lg p-3 {{ $suggestion->status->value === 'stale' ? 'opacity-60' : '' }}">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="badge badge-sm badge-ghost font-mono">
                                {{ $suggestion->field ?? 'block #' . $suggestion->block_id }}
                            </span>
                            @if($suggestion->status->value === 'stale')
                            <span class="badge badge-sm badge-warning">Đã thay đổi — chạy lại AI</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs mb-2">
                            <div>
                                <p class="text-base-content/40 font-medium mb-1">Hiện tại</p>
                                <p class="bg-base-200/50 rounded p-2 whitespace-pre-wrap">{{ \Illuminate\Support\Str::limit($suggestion->original_text, 300) }}</p>
                            </div>
                            <div>
                                <p class="text-base-content/40 font-medium mb-1">Đề xuất AI</p>
                                <p class="bg-success/10 rounded p-2 whitespace-pre-wrap">{{ \Illuminate\Support\Str::limit($suggestion->suggested_text, 300) }}</p>
                            </div>
                        </div>

                        @if($suggestion->reason)
                        <p class="text-xs text-base-content/40 italic mb-2">Lý do: {{ $suggestion->reason }}</p>
                        @endif

                        @if($suggestion->status->value !== 'stale')
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('backend.aicem.generation.suggestions.accept', [$latestRun, $suggestion]) }}">
                                @csrf
                                <button class="btn btn-success btn-xs">Chấp nhận</button>
                            </form>
                            <form method="POST" action="{{ route('backend.aicem.generation.suggestions.reject', [$latestRun, $suggestion]) }}">
                                @csrf
                                <button class="btn btn-ghost btn-xs">Từ chối</button>
                            </form>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            @endif
        @endif
    </div>
</div>
