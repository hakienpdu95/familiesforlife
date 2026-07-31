<div class="container">
    <div class="news-letter homepage-row homepage-news-letter wrapper"
         style="background-image: url('{{ asset('images/banner.jpg') }}');"
         x-data="newsletterSignup({ endpoint: '{{ route('newsletter.public.subscribe') }}' })">

        <div class="nl-panel">
            <div class="nl-content" x-show="!success">
                <h4 class="homepage-mobile-small-title">Newsletter</h4>
                <h3 class="nl_title">Cập Nhật Hoạt Động<br>Mẹo Hay Cho Cả Nhà</h3>
                <h2>Đăng ký để không bỏ lỡ điều gì mới từ {{ config('app.site_name') }}!</h2>

                <div class="nl-form">
                    <form @submit.prevent="submit()" novalidate>
                        <div class="clear-float">
                            <input type="email" class="email" x-model.trim="email" name="email"
                                   placeholder="Email address" required :disabled="loading">
                            <input type="submit" value="đăng ký ngay" :disabled="!canSubmit || loading">
                        </div>
                        <div class="checkbox-wrapper">
                            <input type="checkbox" class="checkbox" x-model="agreed" required :disabled="loading">
                            <div class="label">
                                Tôi đã đọc và đồng ý với
                                <a href="/chinh-sach-bao-mat" target="_blank" rel="noopener">chính sách bảo mật</a>
                                của {{ config('app.site_name') }}.
                            </div>
                        </div>
                        <p class="error-newsletter" x-show="errorMessage" x-cloak x-text="errorMessage"></p>
                    </form>
                </div>
            </div>

            <div class="loading-newsletter" x-show="loading" x-cloak>
                <span class="loading loading-spinner loading-lg"></span>
            </div>

            <div class="callback-newsletter" x-show="success" x-cloak>
                <h3>Cảm ơn bạn đã đăng ký!</h3>
                <h4>Theo dõi hộp thư để nhận bản tin mới nhất</h4>
            </div>
        </div>

    </div>
</div>
