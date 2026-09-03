@extends('layouts.backend')
@section('title', 'Video Series Prompt Studio')

@section('content')
<div class="mb-5 flex items-start justify-between flex-wrap gap-2">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Video Series Prompt Studio</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Danh sách prompt "Series Bible" đã sinh — kiến trúc cho cả chuỗi video nhiều tập (concept, khung 1 tập
            chuẩn lặp lại, dàn ý 5-10 tập đầu tiên), thay vì kịch bản cho 1 video đơn lẻ. Công cụ KHÔNG gọi AI trong
            app — mỗi dòng dưới đây là 1 prompt đã ghép sẵn để copy sang ChatGPT/Claude.
        </p>
    </div>
    <a href="{{ route('backend.videoseriespromptstudio.create') }}" class="btn btn-primary btn-sm">+ Tạo prompt mới</a>
</div>

@if (session('success'))
    <div class="alert alert-success text-sm mb-4">{{ session('success') }}</div>
@endif

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body py-3 px-3">
        @if ($promptList->isEmpty())
            <p class="text-sm text-base-content/50 py-6 text-center">Chưa có prompt nào — bấm "+ Tạo prompt mới" để bắt đầu.</p>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tên gọi</th>
                            <th>Chủ đề</th>
                            <th>Nền tảng</th>
                            <th>Chuyên mục</th>
                            <th>Số tập</th>
                            <th>Ngày tạo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($promptList as $prompt)
                            <tr>
                                <td>
                                    <a href="{{ route('backend.videoseriespromptstudio.show', $prompt) }}" class="link link-hover font-medium">
                                        {{ $prompt->label }}
                                    </a>
                                </td>
                                <td class="text-base-content/60">{{ $prompt->series_topic }}</td>
                                <td class="text-base-content/60">{{ $prompt->platformLabel() }}</td>
                                <td class="text-base-content/60">{{ $prompt->category?->name ?? '—' }}</td>
                                <td>{{ $prompt->episode_count }}</td>
                                <td class="text-base-content/60">{{ $prompt->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('backend.videoseriespromptstudio.show', $prompt) }}" class="btn btn-ghost btn-xs">Xem</a>
                                        <form action="{{ route('backend.videoseriespromptstudio.destroy', $prompt) }}" method="POST"
                                              onsubmit="return confirm('Xoá prompt &quot;{{ $prompt->label }}&quot;?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-ghost btn-xs text-error">Xoá</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $promptList->links() }}</div>
        @endif
    </div>
</div>
@endsection
