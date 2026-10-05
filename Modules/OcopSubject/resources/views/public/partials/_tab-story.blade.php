@if(filled($subject->story))
<div class="prose prose-sm sm:prose-base max-w-none prose-img:rounded-lg prose-img:mx-auto prose-a:text-[#117a3a] [&_table]:block [&_table]:overflow-x-auto">
    {!! $subject->story !!}
</div>
@else
<p class="py-10 text-center text-sm text-base-content/50">Câu chuyện thương hiệu đang được cập nhật.</p>
@endif

@if($gallery->isNotEmpty())
<h3 class="mb-3 mt-8 text-base font-semibold text-[#333]">Hình ảnh cơ sở sản xuất</h3>
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
    @foreach($gallery as $image)
    <a href="{{ $image['full'] }}" target="_blank" rel="noopener" class="block aspect-square overflow-hidden rounded-sm bg-base-200">
        <img src="{{ $image['thumb'] }}" alt="{{ $subject->name }} - ảnh {{ $loop->iteration }}" class="h-full w-full object-cover transition hover:scale-105" loading="lazy">
    </a>
    @endforeach
</div>
@endif
