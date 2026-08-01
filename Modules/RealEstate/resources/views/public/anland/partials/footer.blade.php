{{-- Footer riêng của Anland — nội dung/nhãn hiệu tự viết cho Anland, không dùng chung
     layouts.partials.frontend-footer (footer đó thuộc trang chủ familiesforlife). --}}
<footer class="bg-neutral text-neutral-content mt-16">
    <div class="anland-container py-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 bg-white/10">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M4 21V8l8-5 8 5v13"/><path d="M9 21v-6h6v6"/><path d="M9 12h.01M15 12h.01M12 8h.01"/></svg>
                </span>
                <span class="text-lg font-bold">Anland</span>
            </div>
            <p class="text-sm text-neutral-content/70 leading-relaxed">
                Kênh thông tin nhà đất — tra cứu tin đăng bán/cho thuê đã qua kiểm duyệt, minh bạch pháp lý,
                cập nhật theo từng khu vực.
            </p>
        </div>

        <div>
            <h4 class="font-semibold mb-3">Danh mục</h4>
            <ul class="space-y-2 text-sm text-neutral-content/70">
                <li><a href="{{ route('real-estate.public.sale.index') }}" class="hover:text-neutral-content">Nhà đất bán</a></li>
                <li><a href="{{ route('real-estate.public.rent.index') }}" class="hover:text-neutral-content">Nhà đất cho thuê</a></li>
                <li><a href="{{ route('real-estate.public.sale.index', ['property_type' => 'apartment']) }}" class="hover:text-neutral-content">Căn hộ chung cư</a></li>
                <li><a href="{{ route('real-estate.public.sale.index', ['property_type' => 'land']) }}" class="hover:text-neutral-content">Đất thổ cư</a></li>
            </ul>
        </div>

        <div>
            <h4 class="font-semibold mb-3">Hỗ trợ</h4>
            <ul class="space-y-2 text-sm text-neutral-content/70">
                <li>
                    @auth
                        <a href="{{ route('backend.real-estate.create') }}" class="hover:text-neutral-content">Đăng tin bất động sản</a>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-neutral-content">Đăng tin bất động sản</a>
                    @endauth
                </li>
                <li><span class="opacity-60">Hướng dẫn mua bán (sắp ra mắt)</span></li>
                <li><span class="opacity-60">Câu hỏi thường gặp (sắp ra mắt)</span></li>
            </ul>
        </div>

        <div>
            <h4 class="font-semibold mb-3">Liên hệ</h4>
            <ul class="space-y-2 text-sm text-neutral-content/70">
                <li>{{ config('app.site_name') }}</li>
                <li>Hỗ trợ qua trang liên hệ chung của hệ thống</li>
            </ul>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="anland-container py-4 text-sm text-neutral-content/60 text-center">
            © {{ now()->year }} Anland — {{ config('app.site_name') }}. Bảo lưu mọi quyền.
        </div>
    </div>
</footer>
