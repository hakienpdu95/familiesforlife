@props([
    'featured', // PostArticleTranslation (with article.categories loaded) — "col-middle"
    'side' => null, // Collection<PostArticleTranslation> tối đa 4 bài — 2 "col-left" + 2 "col-right"
])

{{-- vgd-news-hightl-h — cấu trúc DOM + tên class copy sát spec/hero.html (site tham khảo:
     eva.vn), thay khối banner cũ (1 bài + blob trang trí) dưới menu. Nội dung là bài viết thật
     của familiesforlife ($featured/$side do PublicCategoryController::index() truyền vào —
     xem heroSideArticles()), không dùng ảnh/link/tiêu đề của eva.vn.

     Badge góc ảnh trong spec là icon "video"/"HOT" theo taxonomy riêng của eva.vn (không có
     tương đương trong Post) — thay bằng badge tên category (DaisyUI `badge`) ở đúng vị trí đó,
     vẫn giữ nguyên cấu trúc pos-rel/pos-ab overlay góc trên-trái.

     Alpine chỉ đảm nhận 1 hiệu ứng fade-in nhẹ khi tải trang (x-data/x-init) — bản thân layout
     tĩnh, không cần tương tác gì thêm. --}}
@php($side = ($side ?? collect())->values())

<section class="vgd-news-hightl-h transition-opacity duration-700 ease-out"
         x-data="{ shown: false }"
         x-init="requestAnimationFrame(() => shown = true)"
         :class="shown ? 'opacity-100' : 'opacity-0'">
    <div class="container">
        <div class="row">
            <div class="col-left flex-1 mw-0">
                @foreach($side->slice(0, 2) as $t)
                <x-frontend.hero-story :translation="$t" />
                @endforeach
            </div>

            <div class="col-middle pos-rel">
                <article class="vgd-news-hightl-big-h mar-b-20">
                    <figure class="vgd-news-hightl-big-h__bg pos-rel mar-b-20">
                        <a href="{{ route('post.public.article', ['slug' => $featured->slug, 'id' => $featured->id]) }}">
                            <img src="{{ $featured->article?->cover_image_url ?: asset('images/post-cover-placeholder.svg') }}"
                                 alt="{{ $featured->title }}"
                                 class="img-fluid img-cover" loading="lazy">
                        </a>
                    </figure>
                    <header class="vgd-news-hightl-big-h__tit mar-t-10 text-trun line-cl-3">
                        <h3>
                            <a href="{{ route('post.public.article', ['slug' => $featured->slug, 'id' => $featured->id]) }}" class="fw-bold hover-color-link">{{ $featured->title }}</a>
                        </h3>
                    </header>
                </article>
            </div>

            <div class="col-right flex-1 mw-0">
                @foreach($side->slice(2, 2) as $t)
                <x-frontend.hero-story :translation="$t" />
                @endforeach
            </div>
        </div>
    </div>
</section>
