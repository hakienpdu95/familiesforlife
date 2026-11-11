<div class="container">
    <section class="news-letter homepage-row homepage-news-letter wrapper"
             style="background-image: url('{{ asset('images/banner.jpg') }}');"
             aria-labelledby="nl-title"
             x-data="newsletterSignup({ endpoint: '{{ route('newsletter.public.subscribe') }}' })">

        <div class="nl-panel">
            <div class="nl-content" x-show="!success">
                <p class="nl-eyebrow">Bản tin</p>
                <h3 class="nl_title" id="nl-title">Cập nhật hoạt động,<br>mẹo hay cho cả nhà</h3>
                <p class="nl-subtitle">Đăng ký để không bỏ lỡ điều gì mới từ {{ config('app.site_name') }}.</p>

                <form class="nl-form" @submit.prevent="submit()" novalidate>
                    <label for="nl-email" class="sr-only">Địa chỉ email</label>
                    <div class="nl-field">
                        <input type="email" id="nl-email" class="email" x-ref="email" x-model.trim="email" @input="clearError()" name="email"
                               placeholder="Email của bạn" autocomplete="email" inputmode="email"
                               required :disabled="loading"
                               :class="{ 'is-error': errorField === 'email' }"
                               :aria-invalid="errorField === 'email' ? 'true' : 'false'"
                               aria-describedby="nl-error">
                        <button type="submit" class="nl-submit" :disabled="loading">
                            <span class="loading loading-spinner loading-xs" x-show="loading" x-cloak></span>
                            <span>Đăng ký</span>
                        </button>
                    </div>

                    <label class="nl-consent">
                        <input type="checkbox" x-model="agreed" @change="clearError()" required :disabled="loading"
                               :class="{ 'is-error': errorField === 'agreed' }">
                        <span>
                            Tôi đồng ý với
                            <a href="/chinh-sach-bao-mat" target="_blank" rel="noopener">chính sách bảo mật</a>
                            của {{ config('app.site_name') }}.
                        </span>
                    </label>

                    <p class="nl-error" id="nl-error" role="alert" x-show="errorMessage" x-cloak x-text="errorMessage"></p>
                </form>
            </div>

            <div class="nl-done" x-show="success" x-cloak role="status">
                <p class="nl-eyebrow">Đã đăng ký</p>
                <h3 class="nl_title">Cảm ơn bạn!</h3>
                <p class="nl-subtitle">Bản tin mới nhất từ {{ config('app.site_name') }} sẽ được gửi tới hộp thư của bạn.</p>
            </div>
        </div>

    </section>
</div>
