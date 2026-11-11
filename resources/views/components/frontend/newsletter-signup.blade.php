<div class="container">
    <section class="news-letter homepage-row homepage-news-letter wrapper"
             style="background-image: url('{{ asset('images/banner.jpg') }}');"
             aria-labelledby="nl-title"
             x-data="newsletterSignup({ endpoint: '{{ route('newsletter.public.subscribe') }}' })">

        <div class="nl-panel">
            <div class="nl-content" x-show="!success">
                <p class="nl-eyebrow">{{ __('Bản tin') }}</p>
                <h3 class="nl_title" id="nl-title">{{ __('Cập nhật hoạt động,') }}<br>{{ __('mẹo hay cho cả nhà') }}</h3>
                <p class="nl-subtitle">{{ __('Đăng ký để không bỏ lỡ điều gì mới từ :value.', ['value' => config('app.site_name')]) }}</p>

                <form class="nl-form" @submit.prevent="submit()" novalidate>
                    <label for="nl-email" class="sr-only">{{ __('Địa chỉ email') }}</label>
                    <div class="nl-field">
                        <input type="email" id="nl-email" class="email" x-ref="email" x-model.trim="email" @input="clearError()" name="email"
                               placeholder="{{ __('Email của bạn') }}" autocomplete="email" inputmode="email"
                               required :disabled="loading"
                               :class="{ 'is-error': errorField === 'email' }"
                               :aria-invalid="errorField === 'email' ? 'true' : 'false'"
                               aria-describedby="nl-error">
                        <button type="submit" class="nl-submit" :disabled="loading">
                            <span class="loading loading-spinner loading-xs" x-show="loading" x-cloak></span>
                            <span>{{ __('Đăng ký') }}</span>
                        </button>
                    </div>

                    <label class="nl-consent">
                        <input type="checkbox" x-model="agreed" @change="clearError()" required :disabled="loading"
                               :class="{ 'is-error': errorField === 'agreed' }">
                        <span>
                            {{ __('Tôi đồng ý với') }}
                            <a href="/chinh-sach-bao-mat" target="_blank" rel="noopener">{{ __('chính sách bảo mật') }}</a>
                            {{ __('của :value.', ['value' => config('app.site_name')]) }}
                        </span>
                    </label>

                    <p class="nl-error" id="nl-error" role="alert" x-show="errorMessage" x-cloak x-text="errorMessage"></p>
                </form>
            </div>

            <div class="nl-done" x-show="success" x-cloak role="status">
                <p class="nl-eyebrow">{{ __('Đã đăng ký') }}</p>
                <h3 class="nl_title">{{ __('Cảm ơn bạn!') }}</h3>
                <p class="nl-subtitle">{{ __('Bản tin mới nhất từ :value sẽ được gửi tới hộp thư của bạn.', ['value' => config('app.site_name')]) }}</p>
            </div>
        </div>

    </section>
</div>
