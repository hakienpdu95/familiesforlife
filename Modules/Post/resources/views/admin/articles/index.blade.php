@extends('layouts.backend')
@section('title', 'Bài viết')

@section('content')
<div>

    @foreach(['success','error'] as $type)
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
            <h1 class="text-2xl font-bold text-base-content">Bài viết</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Quản lý bài viết theo danh mục</p>
        </div>
        <div class="flex items-center gap-2">
            @can('create', \Modules\Post\Models\PostCategory::class)
            <a href="{{ route('backend.post.categories.index') }}" class="btn btn-ghost btn-sm">Danh mục</a>
            @endcan
            @can('create', \Modules\Post\Models\PostArticle::class)
            <a href="{{ route('backend.post.articles.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm bài viết
            </a>
            @endcan
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tiêu đề..."
               class="input input-bordered input-sm w-56">
        <select name="category_id" class="select select-bordered select-sm">
            <option value="">— Tất cả danh mục —</option>
            @foreach($categories as $c)
            <option value="{{ $c->id }}" @selected((string) request('category_id') === (string) $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="format" class="select select-bordered select-sm">
            <option value="">— Tất cả định dạng —</option>
            @foreach(\Modules\Post\Enums\ArticleFormat::cases() as $f)
            <option value="{{ $f->value }}" @selected(request('format') === $f->value)>{{ $f->label() }}</option>
            @endforeach
        </select>
        <select name="status" class="select select-bordered select-sm">
            <option value="">— Tất cả trạng thái —</option>
            @foreach(\Modules\Post\Enums\TranslationStatus::cases() as $s)
            <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('q') || request('category_id') || request('format') || request('status'))
        <a href="{{ route('backend.post.articles.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Danh mục</th>
                        <th>Định dạng</th>
                        <th class="text-center">Trạng thái</th>
                        <th>Người tạo</th>
                        <th class="w-28"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($articles as $a)
                @php($main = $a->mainTranslation())
                @php($canManage = auth()->user()->can('post_article.edit') && ($a->created_by === auth()->id() || auth()->user()->can('post_article.publish')))
                <tr class="hover">
                    <td>
                        <a href="{{ route('backend.post.articles.show', $a) }}" class="font-medium text-sm link link-hover">
                            {{ $main?->title ?? '(chưa có bản dịch ' . $a->main_locale . ')' }}
                        </a>
                    </td>
                    <td class="text-sm text-base-content/60">
                        {{ $a->categories->pluck('name')->implode(', ') ?: '—' }}
                    </td>
                    <td class="text-sm text-base-content/60">{{ $a->format->label() }}</td>
                    <td class="text-center">
                        @if($main)
                        <span class="badge badge-sm {{ $main->status->badgeClass() }}">{{ $main->status->label() }}</span>
                        @else
                        <span class="badge badge-sm badge-ghost">—</span>
                        @endif
                        {{-- §11/§13 — $a->is_sponsored đã có sẵn qua ListArticlesForAdminHandler
                             (select toàn bộ cột PostArticle mặc định), không thêm query riêng. --}}
                        @if($a->is_sponsored)
                        <span class="badge badge-sm {{ $a->sponsor_label?->badgeClass() ?? 'badge-warning' }}" title="Bài viết tài trợ">🏷</span>
                        @endif
                    </td>
                    <td class="text-sm text-base-content/60">{{ $a->createdBy?->name ?? '—' }}</td>
                    <td>
                        <div class="flex gap-1">
                            @if($a->isRedirect())
                            <a href="{{ route('backend.post.articles.clicks', $a) }}" class="btn btn-ghost btn-xs btn-square" title="Thống kê click">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                            </a>
                            @endif
                            @if($canManage)
                            <a href="{{ route('backend.post.articles.edit', $a) }}" class="btn btn-ghost btn-xs btn-square" title="Sửa">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('backend.post.articles.destroy', $a) }}"
                                  onsubmit="return confirm('Xoá bài viết &quot;{{ $main?->title }}&quot;?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-xs btn-square text-error" title="Xoá">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-8 text-base-content/40">Chưa có bài viết nào.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($articles->hasPages())
        <div class="p-3 border-t border-base-200">{{ $articles->links() }}</div>
        @endif
    </div>
</div>
@endsection
