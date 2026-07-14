@props([
    'categories', // Collection<PostCategory> — dùng link danh mục đầu tiên làm điểm đến CTA
])

{{--
  Thay cho khối "Newsletter" của bản mẫu tĩnh — hệ thống chưa có bảng/route đăng ký nhận bản
  tin, nên KHÔNG dựng UI giả lập "đăng ký thành công" (bản mẫu tĩnh chỉ toggle biến Alpine cục
  bộ, không gọi API nào). Giữ đúng bố cục trang trí (blob + card giữa) nhưng đổi thành CTA dẫn
  người đọc sang danh mục thật, tránh tạo cảm giác một tính năng đang hoạt động mà thực ra không.
--}}
<section class="relative overflow-hidden bg-warning/10 py-14 text-center">
    <div class="blob bg-secondary h-16 w-16 left-10 top-8"></div>
    <div class="blob bg-warning h-20 w-20 left-6 bottom-4"></div>
    <div class="blob bg-primary h-16 w-16 right-10 top-10"></div>
    <div class="blob bg-accent h-24 w-24 right-4 bottom-0"></div>

    <div class="relative max-w-md mx-auto px-4">
        <h2 class="font-black text-2xl text-secondary">Khám Phá Thêm Bài Viết</h2>
        <p class="mt-2 text-sm text-base-content/60">Cẩm nang nuôi dạy con, trường học và trải nghiệm gia đình — cập nhật liên tục.</p>
        @if($categories->isNotEmpty())
        <a href="{{ route('post.public.category', ['category' => $categories->first()->slug]) }}"
           class="btn mt-5 border-none bg-secondary text-white hover:bg-secondary/90 px-8">Xem Ngay</a>
        @endif
    </div>
</section>
