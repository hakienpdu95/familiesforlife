@extends('layouts.backend')
@section('title', 'Banner')

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
            <h1 class="text-2xl font-bold text-base-content">Banner</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Banner quảng cáo/thông báo hiển thị ở nhiều vị trí trên cổng thông tin</p>
        </div>
        <div class="flex items-center gap-2">
            @can('create', \Modules\Banner\Models\Banner::class)
            <a href="{{ route('backend.banner.items.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm banner
            </a>
            @endcan
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <select name="placement" class="select select-bordered select-sm">
            <option value="">— Tất cả vị trí —</option>
            @foreach($placements as $key => $p)
            <option value="{{ $key }}" {{ request('placement') === $key ? 'selected' : '' }}>{{ $p['label'] }}</option>
            @endforeach
        </select>
        <select name="target_type" class="select select-bordered select-sm">
            <option value="">— Tất cả target —</option>
            @foreach($targetTypes as $key => $label)
            <option value="{{ $key }}" {{ request('target_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('placement') || request('target_type'))
        <a href="{{ route('backend.banner.items.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Ảnh</th>
                        <th>Vị trí</th>
                        <th>Target</th>
                        <th>Lịch chạy</th>
                        <th class="text-center">Click</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="w-32"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($banners as $banner)
                    @php
                        $isRunning = $banner->is_active
                            && (! $banner->start_date || $banner->start_date->lte(now()))
                            && (! $banner->end_date || $banner->end_date->gte(now()));
                        $categoryName = $banner->target_type?->value === 'category' && $banner->target_value
                            ? \Modules\Post\Models\PostCategory::where('slug', $banner->target_value)->value('name')
                            : null;
                        $provinceName = $banner->target_type?->value === 'province' && $banner->target_value
                            ? \App\Models\Province::where('province_code', $banner->target_value)->value('name')
                            : null;
                    @endphp
                    <tr>
                        <td>
                            <img src="{{ Illuminate\Support\Facades\Storage::url($banner->image_path) }}" alt=""
                                 class="h-10 w-auto rounded border border-base-300 object-cover">
                        </td>
                        <td>
                            <span class="text-sm">{{ \Modules\Banner\Models\Banner::getPlacementLabel($banner->placement) ?? $banner->placement }}</span>
                            @if($banner->title)
                            <p class="text-xs text-base-content/40">{{ $banner->title }}</p>
                            @endif
                        </td>
                        <td>
                            @if($banner->target_type === null)
                            <span class="badge badge-ghost badge-sm">Toàn site</span>
                            @elseif($banner->target_type->value === 'category')
                                @if($categoryName)
                                <span class="badge badge-info badge-sm">Danh mục: {{ $categoryName }}</span>
                                @else
                                <span class="badge badge-warning badge-sm">Danh mục: (đã xoá)</span>
                                @endif
                            @elseif($banner->target_type->value === 'province')
                                @if($provinceName)
                                <span class="badge badge-info badge-sm">Tỉnh/thành: {{ $provinceName }}</span>
                                @else
                                <span class="badge badge-warning badge-sm">Tỉnh/thành: (không rõ)</span>
                                @endif
                            @endif
                        </td>
                        <td class="text-xs text-base-content/60">
                            @if($banner->start_date || $banner->end_date)
                                {{ $banner->start_date?->format('d/m/Y') ?? '—' }} → {{ $banner->end_date?->format('d/m/Y') ?? '—' }}
                            @else
                                <span class="text-base-content/30">Không giới hạn</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $banner->click_count }}</td>
                        <td class="text-center">
                            @if($isRunning)
                            <span class="badge badge-success badge-sm">Đang chạy</span>
                            @elseif(! $banner->is_active)
                            <span class="badge badge-neutral badge-sm">Đã tắt</span>
                            @else
                            <span class="badge badge-ghost badge-sm">Ngoài lịch</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                @can('update', $banner)
                                <a href="{{ route('backend.banner.items.edit', $banner) }}" class="btn btn-ghost btn-xs">Sửa</a>
                                @endcan
                                @can('delete', $banner)
                                <form method="POST" action="{{ route('backend.banner.items.destroy', $banner) }}"
                                      onsubmit="return confirm('Xoá banner này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-ghost btn-xs text-error">Xoá</button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-8 text-base-content/40">Chưa có banner nào.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($banners->hasPages())
    <div class="mt-4">{{ $banners->onEachSide(1)->links() }}</div>
    @endif
</div>
@endsection
