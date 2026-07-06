@extends('layouts.backend')
@section('title', 'Kết quả — ' . ($session->product?->name ?? 'OCOP'))

@section('content')
<div x-data="{ showDuplicate: false }" class="max-w-2xl mx-auto">

    @foreach(['success','error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             x-transition.opacity.duration.500ms
             class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach
    @if ($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold text-base-content">{{ $session->product?->name ?? 'Luyện tập' }}</h1>
            <p class="text-xs text-base-content/50">
                {{ $session->mode === 'self_assessment' ? 'Tự đánh giá (Mẫu 02)' : 'Luyện tập' }}
                @if ($session->status === 'abandoned') — <span class="text-error">đã bỏ dở</span> @endif
            </p>
        </div>
        @if ($session->ocop_product_id)
        <a href="{{ route('ocop.products.show', $session->ocop_product_id) }}" class="btn btn-ghost btn-sm">← Sản phẩm</a>
        @endif
    </div>

    @if ($session->mode === 'self_assessment')
    <div class="alert alert-warning mb-4 text-sm">
        Đây là ước lượng tự chấm theo Mẫu 02 — kết quả công nhận chính thức do UBND tỉnh/Bộ NN&MT quyết định.
    </div>
    @endif

    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body text-center py-8">
            <p class="text-4xl font-bold">{{ $session->total_score }}đ</p>
            <p class="text-base-content/60 mt-1">
                @if ($session->star_rank)
                    Hạng {{ $session->star_rank }} sao
                @else
                    Chưa đạt hạng sao nào
                @endif
            </p>
            <div class="grid grid-cols-3 gap-4 mt-6 text-sm">
                <div><div class="opacity-50 text-xs">Phần A</div><div class="font-bold">{{ $session->score_section_a }}đ</div></div>
                <div><div class="opacity-50 text-xs">Phần B</div><div class="font-bold">{{ $session->score_section_b }}đ</div></div>
                <div><div class="opacity-50 text-xs">Phần C</div><div class="font-bold">{{ $session->score_section_c }}đ</div></div>
            </div>
        </div>
    </div>

    @if (!empty($quickWins))
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body p-4">
            <h2 class="font-bold text-sm mb-3">Quick win — cải thiện nhanh nhất</h2>
            <div class="flex flex-col gap-2">
                @foreach ($quickWins as $win)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-base-content/70">{{ $win['criterion_label'] }}</span>
                    <span class="badge badge-success badge-sm">+{{ $win['gain'] }}đ</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if ($session->status === 'completed')
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4">
            <button type="button" @click="showDuplicate = !showDuplicate" class="btn btn-outline btn-sm w-full">
                Nhân bản sang sản phẩm khác
            </button>

            <div x-show="showDuplicate" x-cloak class="mt-4">
                <form method="POST" action="{{ route('ocop.practice.duplicate', $session) }}" class="flex flex-col gap-3">
                    @csrf
                    <input type="hidden" name="mode" value="{{ $session->mode }}">

                    <div class="form-control">
                        <label class="label label-text text-xs">Sản phẩm có sẵn</label>
                        <select name="target_product_id" class="select select-bordered select-sm">
                            <option value="">— Hoặc tạo sản phẩm mới bên dưới —</option>
                            @foreach ($otherProducts as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->productGroup?->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="divider text-xs">HOẶC</div>

                    <div class="form-control">
                        <label class="label label-text text-xs">Tên sản phẩm mới (cùng Bộ sản phẩm: {{ $session->rubricVersion->productGroup->name }})</label>
                        <input type="text" name="new_product_name" placeholder="VD: Cam Cao Phong loại 2" class="input input-bordered input-sm">
                        <input type="hidden" name="product_group_id" value="{{ $session->rubricVersion->product_group_id }}">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">Nhân bản</button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
