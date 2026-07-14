@extends('layouts.frontend')

@section('title', $translation->seo_title ?: $translation->title)
@if($translation->seo_description || $translation->excerpt)
@section('meta_description', $translation->seo_description ?: $translation->excerpt)
@endif

@push('meta')
<link rel="canonical" href="{{ route('post.public.article', ['slug' => $translation->slug]) }}">
@endpush

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('post.public.home') }}">Trang Chủ</a></li>
            @if($article->categories->isNotEmpty())
            <li><a href="{{ route('post.public.category', ['category' => $article->categories->first()->slug]) }}">{{ $article->categories->first()->name }}</a></li>
            @endif
        </ul>
    </div>

    {{-- spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §12 — disclosure_text rỗng chỉ có thể xảy ra
         nếu dữ liệu vào thẳng DB bỏ qua Action/validation (vd import tay, sửa trực tiếp) —
         validation ở §6.2 đã chặn ở lối vào bình thường, nhưng khối bắt buộc-không-thể-ẩn theo
         §4.3 v1.0 không nên phụ thuộc HOÀN TOÀN vào validation tầng nhập liệu, nên vẫn guard
         thêm ở tầng hiển thị (defense-in-depth). Đặt NGAY TRÊN tiêu đề, không có toggle/JS nào
         có thể tắt khối này — render server-side vô điều kiện khi isCurrentlySponsored() và có
         disclosure_text. §12.1 — {{ }} (không phải {!! !!}) tự động escape, chặn XSS phản chiếu
         qua sponsor_name/disclosure_text mà không cần xử lý thêm. --}}
    @if($article->isCurrentlySponsored() && $translation->disclosure_text)
    <div class="alert alert-warning mb-4 flex items-center gap-2">
        @if($article->sponsor_logo_url)
        <img src="{{ $article->sponsor_logo_url }}" alt="{{ $article->sponsor_name }}" class="h-6">
        @endif
        <span class="badge {{ $article->sponsor_label->badgeClass() }}">{{ $article->sponsor_label->label() }}</span>
        <span class="text-sm">{{ $translation->disclosure_text }}</span>
    </div>
    @endif

    <span class="text-xs font-black uppercase tracking-wide text-primary">
        {{ $article->categories->first()?->name }}
    </span>
    <h1 class="text-3xl font-bold text-base-content mt-1 mb-2">{{ $translation->title }}</h1>
    <p class="text-sm text-secondary font-semibold mb-4">{{ $translation->published_at?->format('d/m/Y') }}</p>

    @if($article->categories->isNotEmpty())
    <div class="flex flex-wrap gap-1.5 mb-4">
        @foreach($article->categories as $cat)
        <a href="{{ route('post.public.category', ['category' => $cat->slug]) }}" class="badge badge-sm badge-ghost hover:badge-primary">{{ $cat->name }}</a>
        @endforeach
    </div>
    @endif

    @if($translation->excerpt)
    <p class="text-base-content/70 italic mb-4">{{ $translation->excerpt }}</p>
    @endif

    <div class="card bg-base-100 shadow-sm border border-base-300">
        <div class="card-body prose max-w-none">
            {!! $content !!}
        </div>
    </div>

    {{-- §12/§12.1 — CTA render cuối bài dưới dạng <a> thường (không qua
         ArticleContentRenderer::sanitizeTextHtml(), field này chỉ là string thuần không phải
         HTML). rel="sponsored nofollow" set cứng (khuyến nghị Google cho link quảng cáo/tài trợ,
         tránh truyền PageRank cho link ngoài không kiểm soát được nội dung đích) + noopener vì
         mở target="_blank" (chuẩn bảo mật window.opener). Cùng điều kiện isCurrentlySponsored()
         với disclosure — hết hạn tài trợ thì ẩn CTA luôn, nhất quán với việc ẩn disclosure. --}}
    @if($article->isCurrentlySponsored() && $translation->cta_text && $translation->cta_url)
    <div class="mt-4">
        <a href="{{ $translation->cta_url }}" target="_blank" rel="sponsored nofollow noopener"
           class="btn btn-warning btn-sm">{{ $translation->cta_text }}</a>
    </div>
    @endif

    @if($article->tags->isNotEmpty())
    <div class="flex flex-wrap gap-1.5 mt-4">
        @foreach($article->tags as $tag)
        <span class="badge badge-sm badge-outline">#{{ $tag->name }}</span>
        @endforeach
    </div>
    @endif

</div>
@endsection
