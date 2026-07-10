@extends('layouts.backend')
@section('title', 'AICEM — Duyệt ví dụ mẫu')

@section('content')
<div>

    @foreach(['success', 'error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition.opacity.duration.500ms
             class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Duyệt ví dụ mẫu (example_good)</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Đề xuất tự động từ bài viết đã xuất bản và đánh dấu "Nổi bật" — bạn duyệt trước khi lưu vào Knowledge Base
            </p>
        </div>
        <a href="{{ route('backend.aicem.knowledge-documents.index') }}" class="btn btn-ghost btn-sm">← Knowledge Base</a>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <select name="status" class="select select-bordered select-sm" onchange="this.form.submit()">
            <option value="pending" {{ request('status', 'pending') === 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Đã duyệt</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Đã từ chối</option>
            <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Tất cả</option>
        </select>
    </form>

    <div class="space-y-3">
        @forelse($candidates as $candidate)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-medium">{{ $candidate->suggested_title }}</span>
                            <span class="badge badge-sm {{ match($candidate->status->value) {
                                'approved' => 'badge-success',
                                'rejected' => 'badge-ghost',
                                default => 'badge-warning',
                            } }}">{{ $candidate->status->value }}</span>
                        </div>
                        <p class="text-xs text-base-content/40">
                            {{ $candidate->subject_type }}#{{ $candidate->subject_id }} ·
                            tạo lúc {{ $candidate->created_at->format('d/m/Y H:i') }}
                            @if($candidate->reviewer)
                                · duyệt bởi {{ $candidate->reviewer->name }} lúc {{ $candidate->reviewed_at?->format('d/m/Y H:i') }}
                            @endif
                        </p>
                        @if($candidate->suggested_scope)
                        <p class="text-xs text-base-content/40 mt-1">
                            Scope dự kiến:
                            @foreach($candidate->suggested_scope as $key => $values)
                                <span class="mr-2">{{ $key }}: {{ implode(', ', (array) $values) }}</span>
                            @endforeach
                        </p>
                        @endif
                    </div>

                    @if($candidate->status->value === 'pending')
                    <div class="flex gap-2 shrink-0">
                        <form method="POST" action="{{ route('backend.aicem.example-candidates.approve', $candidate) }}">
                            @csrf
                            <button class="btn btn-success btn-xs">Duyệt</button>
                        </form>
                        <form method="POST" action="{{ route('backend.aicem.example-candidates.reject', $candidate) }}"
                              onsubmit="return confirm('Từ chối đề xuất này?')">
                            @csrf
                            <button class="btn btn-ghost btn-xs">Từ chối</button>
                        </form>
                    </div>
                    @endif
                </div>

                <details class="mt-3">
                    <summary class="text-xs text-base-content/50 cursor-pointer select-none">Xem nội dung đề xuất</summary>
                    <pre class="text-xs bg-base-200/50 rounded p-3 mt-2 whitespace-pre-wrap font-sans">{{ $candidate->suggested_content }}</pre>
                </details>
            </div>
        </div>
        @empty
        <div class="text-center py-12 text-base-content/40 text-sm">Không có candidate nào.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $candidates->links() }}</div>
</div>
@endsection
