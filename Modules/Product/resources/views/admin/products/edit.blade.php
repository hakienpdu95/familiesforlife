@extends('layouts.backend')
@section('title', 'Sửa sản phẩm')

{{-- docs/form-ui-spec.md v5.1 — form dùng chung _form.blade.php (tab + Sticky Submit Bar) --}}
@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa sản phẩm</h1>
        <p class="text-sm text-base-content/50 mt-0.5 flex items-center gap-2">
            {{ $product->name }}
            <span class="badge badge-sm {{ $product->status->badgeClass() }}">{{ $product->status->label() }}</span>
            @if ($product->approvalStatus())
                <span class="badge badge-sm {{ $product->approvalStatus()->badgeClass() }}">
                    Duyệt: {{ $product->approvalStatus()->label() }}
                </span>
            @endif
        </p>
    </div>
    <a href="{{ route('backend.products.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

{{-- Error banner --}}
@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

{{-- ── Duyệt nội dung — spec/Workflow_Approval_Technical_Specification.md §9.5 ────────
     Đặt NGOÀI <form> chính (phía trên form) (mỗi nút là 1 form POST riêng, không lồng được vào form
     update sản phẩm). Trục "Duyệt nội dung" độc lập với trục "Trạng thái" (kinh doanh/tồn
     kho) ở Sticky Submit Bar — 2 badge khác nhau ở header, không gộp. --}}
@if ($product->approvalStatus())
<div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
    <div class="card-body p-3">
        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Duyệt nội dung</p>

        <div class="flex flex-wrap items-center gap-2">
            @if ($product->isApprovalDraft() && auth()->user()->can('submitForApproval', $product))
                <form method="POST" action="{{ route('backend.products.submit-approval', $product) }}">
                    @csrf
                    <button class="btn btn-sm btn-warning">Gửi duyệt</button>
                </form>
            @endif

            @if ($product->isApprovalPending() && auth()->user()->can('approve', $product))
                <form method="POST" action="{{ route('backend.products.approve-content', $product) }}">
                    @csrf
                    <button class="btn btn-sm btn-info">Duyệt</button>
                </form>
            @endif

            @if ($product->isApprovalPending() && auth()->user()->can('reject', $product))
                <button type="button" class="btn btn-sm btn-error btn-outline"
                        onclick="document.getElementById('reject-content-modal').showModal()">
                    Từ chối
                </button>
                <dialog id="reject-content-modal" class="modal">
                    <div class="modal-box">
                        <h3 class="font-bold text-base mb-3">Từ chối duyệt nội dung</h3>
                        <form method="POST" action="{{ route('backend.products.reject-content', $product) }}">
                            @csrf
                            <textarea name="reason" required minlength="10" rows="3"
                                      class="textarea textarea-bordered textarea-sm w-full"
                                      placeholder="Lý do từ chối (tối thiểu 10 ký tự)"></textarea>
                            <div class="modal-action">
                                <button type="button" class="btn btn-ghost btn-sm"
                                        onclick="document.getElementById('reject-content-modal').close()">Huỷ</button>
                                <button type="submit" class="btn btn-error btn-sm">Xác nhận từ chối</button>
                            </div>
                        </form>
                    </div>
                </dialog>
            @endif

            @if ($product->isApproved() && auth()->user()->can('publishApproval', $product))
                <form method="POST" action="{{ route('backend.products.publish-content', $product) }}">
                    @csrf
                    <button class="btn btn-sm btn-success">Xuất bản</button>
                </form>
            @endif

            @if ($product->isApprovalPublished() && auth()->user()->can('archiveApproval', $product))
                <form method="POST" action="{{ route('backend.products.archive-content', $product) }}">
                    @csrf
                    <button class="btn btn-sm btn-neutral btn-outline">Lưu trữ</button>
                </form>
            @endif

            @if ($product->isApprovalArchived())
                <span class="text-xs text-base-content/40">Đã lưu trữ — không thể sửa nội dung.</span>
            @endif
        </div>
    </div>
</div>
@endif

@if($product->used_in_articles_count > 0)
<div class="alert alert-info text-xs mb-4">
    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span>Sản phẩm đang được <strong>{{ $product->used_in_articles_count }}</strong> bài viết tham chiếu — không thể xoá cứng, chỉ có thể đổi trạng thái.</span>
</div>
@endif

@include('product::admin.products._form', ['product' => $product])

<div class="mt-6">
    <x-aicem::panel
        :subject-type="'product'"
        :subject-id="$product->id"
        :allowed-fields="config('aicem_subjects.product.fields')"
        :allow-block-edit="config('aicem_subjects.product.has_blocks')"
        :subject-taxonomy-preview="['category_slugs' => $product->category ? [$product->category->slug] : [], 'price_tier' => [\Modules\Aicem\Support\PriceTierBucketer::bucket($product->price !== null ? (float) $product->price : null)], 'link_types' => collect(\Modules\Product\Enums\ProductLinkType::cases())->filter(fn ($t) => filled($product->{$t->urlColumn()}))->map(fn ($t) => $t->value)->values()]"
    />
</div>
@endsection

@push('styles')
    @vite(['Modules/Product/resources/assets/sass/product.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/filepond.js',
        'resources/js/modules/jodit.js',
        'Modules/Product/resources/assets/js/product.js',
    ], 'build/backend')
@endpush
