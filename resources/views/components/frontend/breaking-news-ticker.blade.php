@props([
    'items', // Collection<PostBreakingNews>, đã with('article.categories', 'article.translations')
])

{{-- spec/Breaking_News_Ticker_Technical_Specification.md §0 "Định dạng hiển thị" — xoay vòng
     từng dòng, giờ dùng Swiper (đã có sẵn trong dependencies, resources/js/modules/swiper.js)
     để có hiệu ứng TRƯỢT NGANG (phải → trái) mượt giữa các tiêu đề, thay vì đổi text tức thì.
     Swiper quản lý DOM slide trực tiếp nên khởi tạo bằng vanilla JS (initBreakingNewsTicker()
     trong resources/js/frontend.js), không phải Alpine component như bản trước — Alpine x-for
     tái tạo DOM theo cơ chế riêng, xung đột với cách Swiper tự quản lý .swiper-slide. --}}
@if($items->isNotEmpty())
<div id="breaking-news-ticker" class="breaking-news-ticker bg-error text-error-content"
     data-config="{{ json_encode([
         'items' => $items->map(fn ($n) => [
             'headline' => $n->displayHeadline(),
             'url' => $n->publicTranslation()
                 ? route('post.public.article', ['slug' => $n->publicTranslation()->slug, 'id' => $n->publicTranslation()->id])
                 : '#',
         ])->values(),
         'pollUrl' => route('post.public.breaking-news.current'),
         'rotateMs' => (int) config('post.breaking_news.rotate_seconds', 5) * 1000,
         'pollMs' => (int) config('post.breaking_news.poll_seconds', 60) * 1000,
     ]) }}">
    <div class="container py-2">
        <div class="swiper breaking-news-swiper">
            <div class="swiper-wrapper"></div>
        </div>
    </div>
</div>

@push('scripts')
    @vite(['resources/js/modules/swiper.js'], 'build/frontend')
@endpush
@endif
