# {{ config('app.site_name') }}

> Cẩm nang gia đình — hoạt động, trường học, nuôi dạy con và trải nghiệm cho cả nhà. Bài viết được biên tập bởi đội ngũ tác giả nền tảng, có tên thật và hồ sơ công khai.

Nội dung được tổ chức theo chuyên mục (khớp menu chính của site), mỗi bài viết có tác giả xác thực,
ngày xuất bản/cập nhật rõ ràng, và có thể kèm khối câu hỏi thường gặp (FAQ), hướng dẫn từng bước
(HowTo), hoặc trích dẫn có nguồn — xem structured data (JSON-LD) trên từng trang để biết chi tiết.

## Trang chính

- [Trang chủ]({{ route('post.public.home') }}): Bài viết mới nhất, tin nổi bật, ô tìm kiếm
- [Danh sách tác giả]({{ route('post.public.author-hub.index') }}): Hồ sơ công khai của đội ngũ biên tập, kèm chức danh/bằng cấp chuyên môn nếu có

## Chuyên mục

@foreach($categories as $category)
- [{!! $category->name !!}]({{ route('post.public.category', ['category' => $category->slug]) }})
@endforeach

## Bản Markdown cho AI agent

Mọi URL bài viết hỗ trợ content negotiation — gửi header `Accept: text/markdown` khi tải trang để
nhận bản Markdown thuần (không layout/nav/script) thay vì HTML, tại ĐÚNG cùng 1 URL.

## Optional

- [Sitemap XML]({{ route('post.public.sitemap') }}): Toàn bộ URL bài viết/chuyên mục/tác giả kèm ngày cập nhật, priority, changefreq — dùng khi cần liệt kê đầy đủ thay vì chỉ các mục chính ở trên
