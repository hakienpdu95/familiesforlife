{{-- Partial: single comment (top-level) --}}
<div data-comment-id="{{ $comment->id }}" class="flex gap-3">
    <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center shrink-0 text-xs font-bold text-primary">
        {{ strtoupper(substr($comment->user?->name ?? '?', 0, 1)) }}
    </div>
    <div class="flex-1 min-w-0">
        <div class="bg-base-200/50 rounded-xl px-4 py-3">
            <div class="flex items-center justify-between gap-2 mb-1">
                <span class="text-xs font-semibold text-base-content/70">
                    {{ $comment->user?->name ?? '—' }}
                </span>
                <div class="flex items-center gap-2">
                    @if($comment->is_edited)
                    <span class="text-xs text-base-content/30 italic">đã sửa</span>
                    @endif
                    <span class="text-xs text-base-content/40">{{ $comment->created_at?->format('d/m/Y H:i') }}</span>
                </div>
            </div>
            <p class="text-sm text-base-content/80 whitespace-pre-wrap">{{ $comment->content }}</p>
        </div>

        <div class="flex gap-3 mt-1 ml-1">
            @if(auth()->id() === $comment->user_id)
            <button data-action="delete-comment"
                    data-url="{{ route('backend.tasks.comments.destroy', [$task, $comment]) }}"
                    class="text-xs text-base-content/40 hover:text-error transition-colors">
                Xóa
            </button>
            @endif
        </div>

        {{-- Replies --}}
        @if($comment->replies->isNotEmpty())
        <div class="mt-3 space-y-3 pl-4 border-l-2 border-base-200">
            @foreach($comment->replies as $reply)
            <div data-comment-id="{{ $reply->id }}" class="flex gap-2">
                <div class="w-6 h-6 rounded-full bg-secondary/10 flex items-center justify-center shrink-0 text-xs font-bold text-secondary">
                    {{ strtoupper(substr($reply->user?->name ?? '?', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="bg-base-200/30 rounded-lg px-3 py-2">
                        <div class="flex items-center justify-between gap-2 mb-0.5">
                            <span class="text-xs font-semibold text-base-content/70">{{ $reply->user?->name ?? '—' }}</span>
                            <span class="text-xs text-base-content/40">{{ $reply->created_at?->format('d/m/Y H:i') }}</span>
                        </div>
                        <p class="text-sm text-base-content/80 whitespace-pre-wrap">{{ $reply->content }}</p>
                    </div>
                    @if(auth()->id() === $reply->user_id)
                    <div class="flex gap-3 mt-0.5 ml-1">
                        <button data-action="delete-comment"
                                data-url="{{ route('backend.tasks.comments.destroy', [$task, $reply]) }}"
                                class="text-xs text-base-content/40 hover:text-error transition-colors">
                            Xóa
                        </button>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif

    </div>
</div>
