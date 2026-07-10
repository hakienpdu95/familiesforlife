@extends('layouts.backend')
@section('title', 'AICEM — Knowledge Base')

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

    @if($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Knowledge Base</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Bộ DNA nội dung (giọng văn, đối tượng đọc, quy chuẩn SEO...) dùng làm ngữ cảnh cho AICEM khi soạn bài/sản phẩm
            </p>
        </div>
        @can('create', \Modules\Aicem\Models\AicemKnowledgeDocument::class)
        <a href="{{ route('backend.aicem.knowledge-documents.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm tri thức
        </a>
        @endcan
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm theo tiêu đề..."
               class="input input-bordered input-sm w-56">
        <select name="type" class="select select-bordered select-sm">
            <option value="">— Mọi loại —</option>
            @foreach(array_keys(config('aicem_subjects.knowledge_slot_definitions', [])) as $type)
            <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
        </select>
        <select name="subject_type" class="select select-bordered select-sm">
            <option value="">— Mọi subject —</option>
            @foreach($subjectTypes as $st)
            <option value="{{ $st['key'] }}" {{ request('subject_type') === $st['key'] ? 'selected' : '' }}>{{ $st['label'] }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('q') || request('type') || request('subject_type'))
        <a href="{{ route('backend.aicem.knowledge-documents.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Loại</th>
                        <th>Subject</th>
                        <th>Scope</th>
                        <th class="text-center">Priority</th>
                        <th class="text-center">Version</th>
                        <th>Người tạo</th>
                        <th class="w-24"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($documents as $doc)
                <tr class="hover">
                    <td>
                        <span class="font-medium text-sm">{{ $doc->title }}</span>
                    </td>
                    <td><span class="badge badge-sm badge-ghost font-mono">{{ $doc->type }}</span></td>
                    <td class="text-sm text-base-content/60">
                        {{ $doc->subject_type ?? 'DNA chung' }}
                    </td>
                    <td class="text-sm text-base-content/60">
                        @if($doc->scope)
                            <span class="badge badge-sm badge-info">{{ count($doc->scope) }} điều kiện ({{ $doc->scope_match->value }})</span>
                        @else
                            <span class="text-base-content/30">Mọi bài/sản phẩm</span>
                        @endif
                    </td>
                    <td class="text-center text-sm">{{ $doc->priority }}</td>
                    <td class="text-center text-sm">v{{ $doc->current_version }}</td>
                    <td class="text-sm text-base-content/60">{{ $doc->creator?->name ?? '—' }}</td>
                    <td>
                        <div class="flex gap-1">
                            @can('update', $doc)
                            <a href="{{ route('backend.aicem.knowledge-documents.edit', $doc) }}" class="btn btn-ghost btn-xs btn-square" title="Sửa">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            @endcan
                            @can('delete', $doc)
                            <form method="POST" action="{{ route('backend.aicem.knowledge-documents.destroy', $doc) }}"
                                  onsubmit="return confirm('Xoá tri thức &quot;{{ $doc->title }}&quot;?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-xs btn-square text-error" title="Xoá">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-8 text-base-content/40">Chưa có tri thức nào trong Knowledge Base.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $documents->links() }}</div>
</div>
@endsection
