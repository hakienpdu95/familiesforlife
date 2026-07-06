@extends('layouts.backend')
@section('title', $task->title)

@section('content')

{{-- Page header --}}
<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div class="min-w-0">
        <div class="text-sm breadcrumbs mb-1">
            <ul>
                <li><a href="{{ route('backend.tasks.index') }}">Công việc</a></li>
                @if($task->project)
                <li><a href="{{ route('backend.tasks.index') }}?prj={{ $task->project_id }}">{{ $task->project->name }}</a></li>
                @endif
                <li class="text-base-content/60 truncate max-w-xs">{{ $task->title }}</li>
            </ul>
        </div>
        <div class="flex flex-wrap items-center gap-2 mt-1">
            <span class="badge badge-soft {{ $task->task_type->badgeClass() }}">
                {{ $task->task_type->icon() }} {{ $task->task_type->label() }}
            </span>
            <span class="badge badge-soft {{ $task->status->badgeClass() }}">{{ $task->status->label() }}</span>
            <span class="badge badge-soft {{ $task->priority->badgeClass() }}">{{ $task->priority->label() }}</span>
            @if($task->is_archived)
            <span class="badge badge-warning badge-soft">Đã lưu trữ</span>
            @endif
        </div>
        <h1 class="text-xl font-bold text-base-content mt-2">{{ $task->title }}</h1>
    </div>
    <div class="flex items-center gap-2 shrink-0">

        {{-- Watcher toggle --}}
        <button id="btn-watch"
                data-url="{{ route('backend.tasks.watch', $task) }}"
                data-watching="{{ $isWatching ? '1' : '0' }}"
                class="btn btn-sm gap-1.5 {{ $isWatching ? 'btn-neutral' : 'btn-ghost' }}">
            <svg class="w-3.5 h-3.5" fill="{{ $isWatching ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <span id="btn-watch-label">{{ $isWatching ? 'Đang theo dõi' : 'Theo dõi' }}</span>
        </button>

        @can('update', $task)
        <a href="{{ route('backend.tasks.edit', $task) }}" class="btn btn-warning btn-sm gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Sửa
        </a>
        @endcan
        @can('delete', $task)
        <form method="POST" action="{{ route('backend.tasks.destroy', $task) }}"
              onsubmit="return confirm('Bạn có chắc muốn xóa công việc này?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-error btn-sm gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Xóa
            </button>
        </form>
        @endcan
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_280px] gap-6 items-start">

    {{-- ── Main content ────────────────────────────────────────────────────── --}}
    <div class="space-y-4">

        @if($task->description)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h3 class="text-sm font-semibold text-base-content/60 uppercase tracking-wide mb-3">Mô tả</h3>
                <div class="prose prose-sm max-w-none text-base-content/80">{!! nl2br(e($task->description)) !!}</div>
            </div>
        </div>
        @endif

        {{-- Labels --}}
        @if($task->labels->isNotEmpty())
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h3 class="text-sm font-semibold text-base-content/60 uppercase tracking-wide mb-3">Nhãn</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($task->labels as $label)
                    <span class="badge badge-sm gap-1.5" style="background-color: {{ $label->color_hex }}20; border-color: {{ $label->color_hex }}60; color: {{ $label->color_hex }}">
                        <span class="w-2 h-2 rounded-full" style="background-color: {{ $label->color_hex }}"></span>
                        {{ $label->name }}
                    </span>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Progress --}}
        @if(!$task->is_leaf)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h3 class="text-sm font-semibold text-base-content/60 uppercase tracking-wide mb-3">Tiến độ con</h3>
                <div class="flex items-center gap-3">
                    <progress class="progress progress-primary flex-1" value="{{ $task->progress_pct }}" max="100"></progress>
                    <span class="text-sm font-semibold w-10 text-right">{{ $task->progress_pct }}%</span>
                </div>
                <p class="text-xs text-base-content/50 mt-1">{{ $task->subtask_done }}/{{ $task->subtask_total }} công việc con hoàn thành</p>
            </div>
        </div>
        @endif

        {{-- ── Comments ──────────────────────────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200" id="comments-section">
            <div class="card-body p-5">

                <h3 class="text-sm font-semibold text-base-content/60 uppercase tracking-wide mb-4">
                    Bình luận
                    <span id="comment-count-badge" class="badge badge-neutral badge-sm ml-1">{{ $task->comment_count }}</span>
                </h3>

                {{-- Comment list --}}
                <div id="comment-list" class="space-y-4">
                    @foreach($task->comments as $comment)
                    @include('task::tasks._comment', ['comment' => $comment])
                    @endforeach
                </div>

                {{-- Add comment form --}}
                <div class="mt-5 pt-4 border-t border-base-200">
                    <form id="comment-form" data-url="{{ route('backend.tasks.comments.store', $task) }}"
                          class="space-y-3">
                        @csrf
                        <div class="form-control">
                            <textarea id="comment-content" name="content" rows="3"
                                      class="textarea textarea-bordered textarea-sm w-full"
                                      placeholder="Thêm bình luận..."></textarea>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                Gửi
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

    </div>

    {{-- ── Sidebar ──────────────────────────────────────────────────────────── --}}
    <div class="space-y-4">

        {{-- Info card --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 space-y-3">

                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Chi tiết</p>

                <div class="divide-y divide-base-200 text-sm">

                    <div class="py-2.5 flex justify-between items-start gap-2">
                        <span class="text-base-content/50 shrink-0">Dự án</span>
                        @if($task->project)
                        <a href="{{ route('backend.projects.show', $task->project) }}" class="font-medium text-right hover:text-primary transition-colors">
                            {{ $task->project->name }}
                            <span class="text-xs font-mono text-base-content/40">({{ $task->project->code }})</span>
                        </a>
                        @else
                        <span class="text-base-content/25">—</span>
                        @endif
                    </div>

                    <div class="py-2.5 flex justify-between items-center gap-2">
                        <span class="text-base-content/50 shrink-0">Người thực hiện</span>
                        <span class="font-medium text-right">{{ $task->employee?->full_name ?? '—' }}</span>
                    </div>

                    <div class="py-2.5 flex justify-between items-center gap-2">
                        <span class="text-base-content/50 shrink-0">Cấp độ</span>
                        <span class="badge badge-xs">{{ $task->depth }}</span>
                    </div>

                    @if($task->start_date)
                    <div class="py-2.5 flex justify-between items-center gap-2">
                        <span class="text-base-content/50 shrink-0">Bắt đầu</span>
                        <span>{{ $task->start_date->format('d/m/Y') }}</span>
                    </div>
                    @endif

                    @if($task->due_date)
                    <div class="py-2.5 flex justify-between items-center gap-2">
                        <span class="text-base-content/50 shrink-0">Hạn</span>
                        <span class="{{ now()->gt($task->due_date) && !$task->status->isDone() ? 'text-error font-semibold' : '' }}">
                            {{ $task->due_date->format('d/m/Y') }}
                        </span>
                    </div>
                    @endif

                    @if($task->completed_at)
                    <div class="py-2.5 flex justify-between items-center gap-2">
                        <span class="text-base-content/50 shrink-0">Hoàn thành</span>
                        <span class="text-success">{{ $task->completed_at->format('d/m/Y') }}</span>
                    </div>
                    @endif

                    @if($task->story_points)
                    <div class="py-2.5 flex justify-between items-center gap-2">
                        <span class="text-base-content/50 shrink-0">Story Points</span>
                        <span class="badge badge-neutral badge-sm">{{ $task->story_points }} SP</span>
                    </div>
                    @endif

                    @if($task->estimated_hours)
                    <div class="py-2.5 flex justify-between items-center gap-2">
                        <span class="text-base-content/50 shrink-0">Dự kiến</span>
                        <span>{{ number_format($task->estimated_hours, 1) }}h</span>
                    </div>
                    @endif

                    <div class="py-2.5 flex justify-between items-center gap-2">
                        <span class="text-base-content/50 shrink-0">Đã log</span>
                        <span>{{ number_format($task->logged_hours, 1) }}h</span>
                    </div>

                </div>
            </div>
        </div>

        {{-- Meta --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 space-y-2">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Lịch sử</p>
                <div class="text-xs text-base-content/50 space-y-1.5">
                    <div>Tạo bởi <span class="font-medium text-base-content/70">{{ $task->createdBy?->name ?? '—' }}</span></div>
                    <div>{{ $task->created_at?->format('d/m/Y H:i') }}</div>
                    @if($task->updatedBy)
                    <div class="pt-1 border-t border-base-200">Cập nhật bởi <span class="font-medium text-base-content/70">{{ $task->updatedBy->name }}</span></div>
                    <div>{{ $task->updated_at?->format('d/m/Y H:i') }}</div>
                    @endif
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'Modules/Task/resources/assets/js/task.js',
    ], 'build/backend')
    <script>
    document.addEventListener('DOMContentLoaded', () => {

        // ── Watcher toggle ──────────────────────────────────────────────────
        const btnWatch = document.getElementById('btn-watch');
        if (btnWatch) {
            btnWatch.addEventListener('click', async () => {
                const url = btnWatch.dataset.url;
                try {
                    const res  = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    });
                    const json = await res.json();
                    const label = document.getElementById('btn-watch-label');
                    if (json.watching) {
                        label.textContent = 'Đang theo dõi';
                        btnWatch.classList.replace('btn-ghost', 'btn-neutral');
                        btnWatch.querySelector('svg').setAttribute('fill', 'currentColor');
                    } else {
                        label.textContent = 'Theo dõi';
                        btnWatch.classList.replace('btn-neutral', 'btn-ghost');
                        btnWatch.querySelector('svg').setAttribute('fill', 'none');
                    }
                    if (window.Toast) Toast.success(json.message);
                } catch {
                    if (window.Toast) Toast.error('Có lỗi xảy ra.');
                }
            });
        }

        // ── Add comment form ────────────────────────────────────────────────
        const commentForm = document.getElementById('comment-form');
        if (commentForm) {
            commentForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const textarea = document.getElementById('comment-content');
                const content  = textarea.value.trim();
                if (!content) return;

                const btn = commentForm.querySelector('button[type="submit"]');
                btn.disabled = true;

                try {
                    const res  = await fetch(commentForm.dataset.url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ content }),
                    });

                    if (!res.ok) {
                        const err = await res.json();
                        if (window.Toast) Toast.error(err.message || 'Lỗi khi gửi bình luận.');
                        return;
                    }

                    const { comment } = await res.json();
                    textarea.value = '';

                    const list = document.getElementById('comment-list');
                    list.insertAdjacentHTML('beforeend', _renderComment(comment));

                    // Update comment count
                    const badge = document.getElementById('comment-count-badge');
                    if (badge) badge.textContent = parseInt(badge.textContent || '0') + 1;

                    if (window.Toast) Toast.success('Đã thêm bình luận.');
                } catch {
                    if (window.Toast) Toast.error('Có lỗi xảy ra khi gửi bình luận.');
                } finally {
                    btn.disabled = false;
                }
            });
        }

        // ── Delete comment (event delegation) ──────────────────────────────
        document.getElementById('comment-list')?.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-action="delete-comment"]');
            if (!btn) return;
            if (!confirm('Xóa bình luận này?')) return;

            const url = btn.dataset.url;
            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                if (res.ok) {
                    btn.closest('[data-comment-id]').remove();
                    const badge = document.getElementById('comment-count-badge');
                    if (badge) badge.textContent = Math.max(0, parseInt(badge.textContent || '1') - 1);
                    if (window.Toast) Toast.success('Đã xóa bình luận.');
                }
            } catch {
                if (window.Toast) Toast.error('Có lỗi xảy ra.');
            }
        });

        function _renderComment(c) {
            const escaped = c.content.replace(/</g, '&lt;').replace(/>/g, '&gt;');
            const canDelete = true; // own comment
            return `
            <div data-comment-id="${c.id}" class="flex gap-3">
                <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center shrink-0 text-xs font-bold text-primary">
                    ${(c.user_name || '?').charAt(0).toUpperCase()}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="bg-base-200/50 rounded-xl px-4 py-3">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-xs font-semibold text-base-content/70">${c.user_name || ''}</span>
                            <span class="text-xs text-base-content/40">${c.created_at}</span>
                        </div>
                        <p class="text-sm text-base-content/80 whitespace-pre-wrap">${escaped}</p>
                    </div>
                    ${canDelete ? `
                    <div class="flex gap-3 mt-1 ml-1">
                        <button data-action="delete-comment" data-url="/dashboard/tasks/{{ $task->id }}/comments/${c.id}"
                                class="text-xs text-base-content/40 hover:text-error transition-colors">Xóa</button>
                    </div>` : ''}
                </div>
            </div>`;
        }
    });
    </script>
@endpush
