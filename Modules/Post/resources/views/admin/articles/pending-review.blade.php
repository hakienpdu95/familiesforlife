@extends('layouts.backend')
@section('title', 'Bài viết chờ duyệt')

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

    <div class="mb-5">
        <h1 class="text-2xl font-bold text-base-content">Bài viết chờ duyệt</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Bài viết đang chờ bạn duyệt — của tất cả tổ chức trên nền tảng
            (spec/Workflow_Approval_Technical_Specification.md §18.10).
        </p>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Bài viết</th>
                        <th>Tổ chức</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-center">Gửi lúc</th>
                        <th class="w-32"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($translations as $translation)
                    <tr class="hover">
                        <td>
                            <span class="font-medium text-sm">{{ $translation->title }}</span>
                            <div class="text-xs text-base-content/40">{{ strtoupper($translation->locale) }}</div>
                        </td>
                        <td class="text-sm text-base-content/60">
                            {{ $translation->article->organization->name ?? '—' }}
                        </td>
                        <td class="text-center">
                            <span class="badge badge-sm {{ $translation->status->badgeClass() }}">{{ $translation->status->label() }}</span>
                        </td>
                        <td class="text-center text-xs text-base-content/40">
                            {{ $translation->updated_at->diffForHumans() }}
                        </td>
                        <td>
                            <a href="{{ route('backend.post.articles.edit', $translation->article) }}?locale={{ $translation->locale }}"
                               class="btn btn-sm btn-primary">
                                Xem &amp; duyệt
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-8 text-base-content/40">
                            Không có bài viết nào đang chờ bạn duyệt.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
