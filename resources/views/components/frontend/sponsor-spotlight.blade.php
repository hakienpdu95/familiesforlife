@props([
    'sponsored', // Collection<PostArticleTranslation> — bài is_sponsored, mới nhất trước
    'locale',
])

{{--
  Thay cho khối "Sự Kiện Cho Bé" của bản mẫu tĩnh (spec/honeykids/honeykids-home.html) — module
  Post chưa có domain "sự kiện", nhưng đã có sẵn cơ chế bài viết tài trợ (is_sponsored,
  sponsor_name, sponsor_label — spec/dac-ta-ky-thuat-bai-viet-tai-tro.md) nên tái dùng dữ liệu
  thật này để giữ đúng bố cục 1 khối lớn + danh sách bên cạnh, thay vì bịa dữ liệu event không
  tồn tại trong schema.
--}}
@if($sponsored->isNotEmpty())
@php
    $lead = $sponsored->first();
    $rest = $sponsored->slice(1);
@endphp
<section class="bg-neutral text-neutral-content pt-12 pb-10">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-center font-normal text-3xl tracking-wide mb-10">Đối Tác Đồng Hành</h2>

        <div class="grid lg:grid-cols-[5fr_7fr] gap-6 items-stretch">
            <a href="{{ route('post.public.article', ['locale' => $locale, 'slug' => $lead->slug]) }}" class="group flex flex-col h-full">
                <div class="flex-1 min-h-[220px] bg-base-200">
                    @if($lead->article?->cover_image_url)
                    <img src="{{ $lead->article->cover_image_url }}" alt="{{ $lead->title }}" class="h-full w-full object-cover">
                    @else
                    <div class="ph h-full w-full"></div>
                    @endif
                </div>
                <div class="flex bg-base-100 text-base-content flex-none">
                    <span class="flex-none w-1.5 bg-primary"></span>
                    <div class="px-4 py-3">
                        <span class="text-[11px] font-black uppercase tracking-wide text-primary">{{ $lead->article?->sponsor_label?->label() ?? 'Bài Viết Tài Trợ' }}</span>
                        <h3 class="font-bold leading-snug truncate group-hover:text-primary">{{ $lead->title }}</h3>
                    </div>
                </div>
            </a>

            <div class="flex flex-col h-full">
                <ul class="flex flex-col gap-1 flex-1 justify-between">
                    @foreach($rest as $t)
                    <li>
                        <a href="{{ route('post.public.article', ['locale' => $locale, 'slug' => $t->slug]) }}" class="group flex bg-base-100 text-base-content">
                            <span class="flex-none w-1.5 bg-primary"></span>
                            <div class="px-4 py-3">
                                <div class="text-[11px] font-black uppercase tracking-wide text-secondary">{{ $t->article?->sponsor_name ?? 'Đối tác' }}</div>
                                <h3 class="font-bold leading-snug group-hover:text-primary">{{ $t->title }}</h3>
                            </div>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>
@endif
