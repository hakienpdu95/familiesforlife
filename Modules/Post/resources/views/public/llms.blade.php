# {{ config('app.site_name') }}

> Cẩm nang gia đình — hoạt động, trường học, nuôi dạy con và trải nghiệm cho cả nhà. Bài viết được biên tập bởi đội ngũ tác giả nền tảng, có tên thật và hồ sơ công khai.

## Trang chính
- [Trang chủ]({{ route('post.public.home') }}): Bài viết mới nhất, tin nổi bật, tìm kiếm
- [Danh sách tác giả]({{ route('post.public.author-hub.index') }}): Hồ sơ công khai của đội ngũ biên tập

## Chuyên mục
@foreach($categories as $category)
- [{!! $category->name !!}]({{ route('post.public.category', ['category' => $category->slug]) }})
@endforeach

## Dữ liệu máy đọc
- [Sitemap XML]({{ route('post.public.sitemap') }}): Toàn bộ URL bài viết/chuyên mục/tác giả kèm ngày cập nhật
