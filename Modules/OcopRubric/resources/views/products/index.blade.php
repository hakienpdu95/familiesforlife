@extends('layouts.backend')
@section('title', 'Sản phẩm OCOP')

@section('content')
<div x-data="{ confirmDelete: null }">

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
            <h1 class="text-2xl font-bold text-base-content">Sản phẩm OCOP</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Danh sách sản phẩm OCOP của tổ chức bạn</p>
        </div>
        @can(\App\Enums\PermissionEnum::OCOP_PRODUCT_MANAGE->value)
        <a href="{{ route('ocop.products.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Đăng ký sản phẩm
        </a>
        @endcan
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tên sản phẩm..."
               class="input input-bordered input-sm w-56">
        <select name="product_group_id" class="select select-bordered select-sm">
            <option value="">— Tất cả bộ sản phẩm —</option>
            @foreach($groups as $g)
            <option value="{{ $g->id }}" @selected((string) request('product_group_id') === (string) $g->id)>{{ $g->name }}</option>
            @endforeach
        </select>
        <select name="status" class="select select-bordered select-sm">
            <option value="">— Tất cả trạng thái —</option>
            @foreach(['draft' => 'Nháp', 'practicing' => 'Đang luyện tập', 'self_assessed' => 'Đã tự đánh giá', 'archived' => 'Đã lưu trữ'] as $value => $label)
            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('q') || request('product_group_id') || request('status'))
        <a href="{{ route('ocop.products.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Bộ sản phẩm</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-right">Điểm luyện tập tốt nhất</th>
                        <th class="text-right">Điểm tự đánh giá mới nhất</th>
                        <th class="w-16"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($products as $p)
                <tr class="hover">
                    <td>
                        <a href="{{ route('ocop.products.show', $p) }}" class="font-medium text-sm link link-hover">{{ $p->name }}</a>
                        @if ($p->product_code)
                        <div class="text-xs text-base-content/40 font-mono">{{ $p->product_code }}</div>
                        @endif
                    </td>
                    <td class="text-sm text-base-content/60">{{ $p->productGroup?->name }}</td>
                    <td class="text-center">
                        @php
                            $badge = match($p->status) {
                                'self_assessed' => 'badge-success',
                                'practicing'    => 'badge-info',
                                'archived'      => 'badge-ghost',
                                default         => 'badge-outline',
                            };
                        @endphp
                        <span class="badge {{ $badge }} badge-sm">{{ $p->status }}</span>
                    </td>
                    <td class="text-right text-sm">
                        @if ($p->best_practice_score !== null)
                            {{ $p->best_practice_score }}đ · {{ $p->best_practice_star_rank }}★
                        @else
                            <span class="text-base-content/30">—</span>
                        @endif
                    </td>
                    <td class="text-right text-sm">
                        @if ($p->latest_self_assessment_score !== null)
                            {{ $p->latest_self_assessment_score }}đ · {{ $p->latest_self_assessment_star_rank }}★
                        @else
                            <span class="text-base-content/30">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('ocop.products.show', $p) }}" class="btn btn-ghost btn-xs btn-square" title="Xem">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-8 text-base-content/40">Chưa có sản phẩm nào.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
