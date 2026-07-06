@extends('layouts.backend')
@section('title', 'Cây tiêu chí — ' . $productGroup->name)

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
    @if ($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    @php $isDraft = $version->status === 'draft'; @endphp

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">
                {{ $productGroup->name }} — v{{ $version->version_no }}
            </h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                @php
                    $badge = match($version->status) {
                        'active'  => 'badge-success',
                        'draft'   => 'badge-warning',
                        'retired' => 'badge-ghost',
                        default   => 'badge-outline',
                    };
                @endphp
                <span class="badge {{ $badge }} badge-sm align-middle">{{ $version->status }}</span>
                <span class="align-middle ml-1">Tổng {{ $version->total_max_score }}đ · {{ $version->source_reference }}</span>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('ocop_rubric.admin.product-groups.index') }}" class="btn btn-ghost btn-sm">← Danh mục Bộ sản phẩm</a>
            <button type="button" class="btn btn-outline btn-sm"
                    onclick="ocopValidate('{{ route('ocop_rubric.admin.product-groups.versions.validate', [$productGroup, $version]) }}')">
                Kiểm tra toàn vẹn
            </button>
            @if ($isDraft)
            <form method="POST" action="{{ route('ocop_rubric.admin.product-groups.versions.publish', [$productGroup, $version]) }}"
                  onsubmit="return confirm('Publish version này? Version active hiện tại (nếu có) sẽ chuyển sang retired.')">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">Publish</button>
            </form>
            @endif
        </div>
    </div>

    <div id="validate-result" class="hidden alert mb-4 text-sm"></div>

    @if ($version->disqualifiers->isNotEmpty())
    <div class="alert alert-warning mb-4 text-sm">
        <div>
            <strong class="block mb-1">Hồ sơ bị loại khi:</strong>
            <ul class="list-disc list-inside">
                @foreach ($version->disqualifiers as $d)
                <li>{{ $d->label }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        @foreach ($version->sections as $section)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="font-bold text-sm">
                        Phần {{ $section->code }}: {{ $section->label }}
                    </h2>
                    <span class="badge badge-neutral badge-sm">{{ $section->max_score }}đ</span>
                </div>

                <ul>
                    @foreach ($section->criteria as $root)
                        @include('ocoprubric::admin.rubric-authoring._criterion-node', ['node' => $root, 'isDraft' => $isDraft])
                    @endforeach
                </ul>

                @if ($isDraft)
                <details class="mt-2">
                    <summary class="text-xs link link-primary cursor-pointer">+ Thêm Mục</summary>
                    <form method="POST" action="{{ route('ocop_rubric.admin.criteria.store') }}" class="flex flex-col gap-2 mt-2">
                        @csrf
                        <input type="hidden" name="rubric_section_id" value="{{ $section->id }}">
                        <input type="text" name="code" placeholder="Mã (VD: 1)" class="input input-bordered input-xs w-full" required>
                        <input type="text" name="label" placeholder="Nhãn (VD: TỔ CHỨC SẢN XUẤT)" class="input input-bordered input-xs w-full" required>
                        <input type="number" step="0.01" name="max_score" placeholder="Điểm tối đa" class="input input-bordered input-xs w-full" required>
                        <button type="submit" class="btn btn-primary btn-xs">Thêm</button>
                    </form>
                </details>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
function ocopValidate(url) {
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var box = document.getElementById('validate-result');
            box.classList.remove('hidden', 'alert-success', 'alert-error');
            if (data.valid) {
                box.classList.add('alert-success');
                box.textContent = 'Hợp lệ — tổng điểm 3 phần và các Mục con đều khớp, có thể publish.';
            } else {
                box.classList.add('alert-error');
                var html = '<ul class="list-disc list-inside">';
                data.errors.forEach(function (e) {
                    html += '<li>' + e.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</li>';
                });
                html += '</ul>';
                box.innerHTML = html;
            }
        });
}
</script>
@endpush
