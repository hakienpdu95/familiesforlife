# {!! config('app.site_name') !!}

> {!! __('Cẩm nang gia đình — hoạt động, trường học, nuôi dạy con và trải nghiệm cho cả nhà. Bài viết được biên tập bởi đội ngũ tác giả nền tảng, có tên thật và hồ sơ công khai.') !!}

{!! __('Nội dung được tổ chức theo chuyên mục (khớp menu chính của site), mỗi bài viết có tác giả xác thực, ngày xuất bản/cập nhật rõ ràng, và có thể kèm khối câu hỏi thường gặp (FAQ), hướng dẫn từng bước (HowTo), hoặc trích dẫn có nguồn — xem structured data (JSON-LD) trên từng trang để biết chi tiết.') !!}

## {!! __('Trang chính') !!}

- [{!! __('Trang chủ') !!}]({{ route('post.public.home') }}): {!! __('Bài viết mới nhất, tin nổi bật, ô tìm kiếm') !!}
- [{!! __('Danh sách tác giả') !!}]({{ route('post.public.author-hub.index') }}): {!! __('Hồ sơ công khai của đội ngũ biên tập, kèm chức danh/bằng cấp chuyên môn nếu có') !!}

## {!! __('Chuyên mục_List') !!}

@foreach($categories as $category)
- [{!! $category->name !!}]({{ route('post.public.category', ['category' => $category->slug]) }})
@endforeach

## {!! __('Bản Markdown cho AI agent') !!}

{!! __('Mọi URL bài viết hỗ trợ content negotiation — gửi header `Accept: text/markdown` khi tải trang để nhận bản Markdown thuần (không layout/nav/script) thay vì HTML, tại ĐÚNG cùng 1 URL.') !!}

## {!! __('Ngôn ngữ khác') !!}

@foreach($otherLocales as $locale => $url)
- [{!! \App\Support\I18n\PortalLocale::label($locale) !!}]({{ $url }})
@endforeach

## Optional

- [{!! __('Sitemap XML') !!}]({{ route('post.public.sitemap') }}): {!! __('Toàn bộ URL bài viết/chuyên mục/tác giả kèm ngày cập nhật, priority, changefreq — dùng khi cần liệt kê đầy đủ thay vì chỉ các mục chính ở trên') !!}
