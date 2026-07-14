@extends('layouts.backend')
@section('title', 'Điều hướng menu')

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

    @if($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Điều hướng menu</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Cây menu header/footer — tối đa 3 cấp, tách biệt danh mục bài viết</p>
        </div>
        <div class="flex items-center gap-2">
            @can('create', \Modules\Menu\Models\MenuItem::class)
            <a href="{{ route('backend.menu.items.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm mục menu
            </a>
            @endcan
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <select name="location" class="select select-bordered select-sm">
            <option value="">— Tất cả vị trí —</option>
            @foreach(config('menu.locations') as $value => $labelText)
            <option value="{{ $value }}" {{ request('location') === $value ? 'selected' : '' }}>{{ $labelText }}</option>
            @endforeach
        </select>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm nhãn menu..."
               class="input input-bordered input-sm w-56">
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('q') || request('location'))
        <a href="{{ route('backend.menu.items.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Nhãn</th>
                        <th class="text-center">Vị trí</th>
                        <th>Đích liên kết</th>
                        <th class="text-center w-24">Thứ tự</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="w-24"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($tree as $root)
                    @include('menu::admin.menu-items._row', ['item' => $root, 'depth' => 0, 'siblings' => $tree])
                    @foreach($root->children as $child)
                        @include('menu::admin.menu-items._row', ['item' => $child, 'depth' => 1, 'siblings' => $root->children])
                        @foreach($child->children as $grandchild)
                            @include('menu::admin.menu-items._row', ['item' => $grandchild, 'depth' => 2, 'siblings' => $child->children])
                        @endforeach
                    @endforeach
                @empty
                <tr>
                    <td colspan="6" class="text-center py-8 text-base-content/40">Chưa có mục menu nào.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
