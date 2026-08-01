@extends('layouts.backend')
@section('title', 'Bài viết cần rà soát')

@section('content')
<div>
    <div class="mb-5">
        <h1 class="text-2xl font-bold text-base-content">Bài viết cần rà soát</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Bài viết đã xuất bản, xem nhiều nhưng lâu chưa cập nhật (quá 90 ngày) — ưu tiên rà soát nội dung/số
            liệu trước, theo đúng gợi ý "rà soát định kỳ mỗi quý" ở tab GEO Checklist của trình soạn bài.
            Danh sách chỉ liệt kê theo lượt xem + ngày cập nhật sẵn có, không tự đánh giá nội dung còn đúng hay không.
        </p>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Bài viết</th>
                        <th class="text-center">Lượt xem</th>
                        <th class="text-center">Cập nhật lần cuối</th>
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
                        <td class="text-center text-sm text-base-content/60">
                            {{ number_format($translation->view_count) }}
                        </td>
                        <td class="text-center">
                            <span class="text-xs text-base-content/60">{{ $translation->updated_at->format('d/m/Y') }}</span>
                            <div class="badge badge-sm badge-warning mt-0.5">{{ $translation->updated_at->diffForHumans() }}</div>
                        </td>
                        <td>
                            <a href="{{ route('backend.post.articles.edit', $translation->article) }}?locale={{ $translation->locale }}"
                               class="btn btn-sm btn-primary">
                                Rà soát
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-8 text-base-content/40">
                            Không có bài viết nào cần rà soát — mọi bài xem nhiều đều đã cập nhật trong 90 ngày qua.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
