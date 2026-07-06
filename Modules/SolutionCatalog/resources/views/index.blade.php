@extends('layouts.backend')
@section('title', 'Danh mục Business Solution')

@section('content')
<div>
    @foreach(['success','error'] as $type)
        @if(session($type))
        <div class="alert alert-{{ $type }} mb-4 text-sm"><span>{{ session($type) }}</span></div>
        @endif
    @endforeach

    <div class="mb-5">
        <h1 class="text-2xl font-bold text-base-content">Danh mục Business Solution</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Chọn giải pháp phù hợp và kích hoạt cho tổ chức của bạn. Không phải danh sách workflow kỹ thuật —
            đây là các giải pháp nghiệp vụ hoàn chỉnh (đúng A02 §10.1).
        </p>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tên giải pháp..."
               class="input input-bordered input-sm w-56">
        <select name="vertical_id" class="select select-bordered select-sm">
            <option value="">— Tất cả vertical —</option>
            @foreach($verticals as $vertical)
            <option value="{{ $vertical->id }}" @selected(request('vertical_id') == $vertical->id)>{{ $vertical->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('q') || request('vertical_id'))
        <a href="{{ route('solution_catalog.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($solutions as $solution)
        <a href="{{ route('solution_catalog.show', $solution) }}"
           class="card bg-base-100 shadow-sm border border-base-200 hover:border-primary/40 transition-colors">
            <div class="card-body p-4">
                @if ($solution->thumbnail_url)
                <img src="{{ $solution->thumbnail_url }}" alt="" class="rounded-lg mb-2 aspect-video object-cover">
                @endif
                <div class="flex items-center justify-between gap-2">
                    <h2 class="font-bold text-base">{{ $solution->name }}</h2>
                    <span class="badge badge-ghost badge-sm">{{ $solution->vertical?->name }}</span>
                </div>
                <p class="text-sm text-base-content/60 line-clamp-2">{{ $solution->short_description }}</p>

                @if (!empty($solution->target_customers))
                <div class="flex flex-wrap gap-1 mt-2">
                    @foreach ($solution->target_customers as $customer)
                    <span class="badge badge-outline badge-xs">{{ $customer }}</span>
                    @endforeach
                </div>
                @endif

                <div class="flex flex-wrap gap-1 mt-2">
                    @foreach ($solution->tags as $tag)
                    <span class="badge badge-ghost badge-xs">#{{ $tag->tag }}</span>
                    @endforeach
                </div>

                <div class="mt-3 text-xs text-base-content/40">
                    @php $blueprint = $solution->blueprints->first(); @endphp
                    @if ($blueprint)
                    Version hiện hành: <span class="font-mono">{{ $blueprint->currentVersion?->version }}</span>
                    @else
                    Chưa có Blueprint published
                    @endif
                </div>
            </div>
        </a>
        @empty
        <p class="text-sm text-base-content/40 col-span-full text-center py-10">Chưa có Business Solution nào được phát hành.</p>
        @endforelse
    </div>
</div>
@endsection
