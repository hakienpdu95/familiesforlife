@extends('layouts.backend')
@section('title', 'Lịch sử luyện tập OCOP')

@section('content')
<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Lịch sử luyện tập</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Toàn bộ phiên đã hoàn thành hoặc bỏ dở của tổ chức bạn</p>
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <select name="product_id" class="select select-bordered select-sm">
            <option value="">— Tất cả sản phẩm —</option>
            @foreach ($products as $p)
            <option value="{{ $p->id }}" @selected((string) request('product_id') === (string) $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
        <select name="mode" class="select select-bordered select-sm">
            <option value="">— Tất cả chế độ —</option>
            <option value="practice" @selected(request('mode') === 'practice')>Luyện tập</option>
            <option value="self_assessment" @selected(request('mode') === 'self_assessment')>Tự đánh giá</option>
        </select>
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('product_id') || request('mode'))
        <a href="{{ route('ocop.practice.history') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Chế độ</th>
                        <th>Người thực hiện</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-right">Điểm</th>
                        <th class="text-center">Hạng sao</th>
                        <th>Hoàn thành lúc</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($history as $s)
                <tr class="hover">
                    <td>
                        @if ($s->ocop_product_id)
                        <a href="{{ route('ocop.products.show', $s->ocop_product_id) }}" class="link link-hover text-sm">{{ $s->product?->name }}</a>
                        @else
                        <span class="text-sm text-base-content/40">—</span>
                        @endif
                    </td>
                    <td class="text-sm">{{ $s->mode === 'self_assessment' ? 'Tự đánh giá' : 'Luyện tập' }}</td>
                    <td class="text-sm text-base-content/60">{{ $s->user?->name ?? '—' }}</td>
                    <td class="text-center">
                        <span class="badge badge-sm {{ $s->status === 'completed' ? 'badge-success' : 'badge-ghost' }}">
                            {{ $s->status === 'completed' ? 'Hoàn thành' : 'Bỏ dở' }}
                        </span>
                    </td>
                    <td class="text-right text-sm">{{ $s->total_score }}đ</td>
                    <td class="text-center text-sm">{{ $s->star_rank ? $s->star_rank . '★' : '—' }}</td>
                    <td class="text-sm text-base-content/60">{{ $s->completed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-8 text-base-content/40">Chưa có phiên nào.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($history->hasPages())
        <div class="px-4 py-3 border-t border-base-200">
            {{ $history->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
