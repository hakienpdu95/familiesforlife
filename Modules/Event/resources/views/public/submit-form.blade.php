@extends('layouts.frontend')

@section('title', 'Gửi Sự Kiện')
@section('meta_description', 'Chia sẻ sự kiện dành cho gia đình và trẻ em lên cổng thông tin — miễn phí, chỉ cần điền form.')

@section('content')
@php
    $section = 'mb-6 border-b border-gray-200 pb-3 text-lg font-bold uppercase tracking-widest text-gray-900';
    $label = 'mb-2 block text-sm font-medium text-gray-700';
    $req = '<span class="text-primary">*</span>';
    $help = 'mt-1 text-xs text-gray-500';
    $err = 'mt-1 text-xs text-error';
    $field = fn (string $name) => 'block w-full rounded-none border bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none focus:ring-0 '
        .($errors->has($name) ? 'border-error' : 'border-gray-300');
    $choice = 'flex cursor-pointer items-center gap-2.5 text-sm font-medium text-gray-800';
    $radio = 'h-4 w-4 accent-primary';
@endphp
<div class="container">
<div class="mx-auto max-w-4xl py-6">

    <header class="text-center">
        <span class="text-xs font-bold uppercase tracking-widest text-primary">Gửi sự kiện</span>
        <h1 class="mt-3 font-serif text-3xl font-bold text-gray-900 lg:text-4xl">Chia Sẻ Sự Kiện Của Bạn</h1>
        <p class="mx-auto mt-3 max-w-2xl text-sm leading-relaxed text-gray-500">Sự kiện sẽ được đội ngũ biên tập xem xét trước khi hiển thị công khai. Email của bạn sẽ KHÔNG hiển thị ở bất kỳ đâu.</p>
    </header>

    @if($errors->any())
    <div class="mt-10 border-l-4 border-error bg-error/5 px-5 py-4 text-sm text-gray-800" role="alert">
        <p class="font-semibold text-error">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-2 list-inside list-disc space-y-0.5 text-xs">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('event.public.submit.store') }}" enctype="multipart/form-data"
          data-event-submit-form class="event-submit-flat"
          x-data="{ locationType: '{{ old('location_type', 'physical') }}', priceType: '{{ old('price_type', 'free') }}' }">
        @csrf

        <section class="mt-12">
            <h2 class="{{ $section }}">Thông tin sự kiện</h2>
            <div class="space-y-6">
                <div class="form-control">
                    <label class="{{ $label }}">Danh mục {!! $req !!}</label>
                    <select name="category_id" required class="w-full ts-init">
                        <option value=""></option>
                        @foreach($eventCategories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @foreach($cat->children as $child)
                            <option value="{{ $child->id }}" {{ old('category_id') == $child->id ? 'selected' : '' }}>&nbsp;&nbsp;— {{ $child->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                    @error('category_id')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $label }}">Tiêu đề {!! $req !!}</label>
                    <input type="text" name="title" value="{{ old('title') }}" required maxlength="150"
                           class="{{ $field('title') }}" placeholder="Ví dụ: Ngày hội gia đình mùa thu 2026">
                    <p class="{{ $help }}">Vui lòng không viết hoa toàn bộ tiêu đề.</p>
                    @error('title')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>

                <div x-data="{ len: {{ strlen(old('short_title', '')) }} }">
                    <label class="{{ $label }} flex items-baseline justify-between">
                        <span>Tiêu đề rút gọn {!! $req !!}</span>
                        <span class="text-xs font-normal" :class="len > 55 ? 'text-error' : 'text-gray-400'" x-text="len + ' / 55 ký tự'"></span>
                    </label>
                    <input type="text" name="short_title" value="{{ old('short_title') }}" required maxlength="55"
                           x-on:input="len = $event.target.value.length"
                           class="{{ $field('short_title') }}">
                    <p class="{{ $help }}">Hiển thị ở thẻ sự kiện và các danh sách thu gọn.</p>
                    @error('short_title')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $label }}">Mô tả {!! $req !!}</label>
                    <textarea name="description" rows="6" required
                              class="{{ $field('description') }} leading-relaxed"
                              placeholder="Giới thiệu nội dung, đối tượng tham gia, chương trình...">{{ old('description') }}</textarea>
                    <p class="{{ $help }}">Không chèn liên kết (http://, https://, www.) — hệ thống sẽ coi đây là spam và từ chối.</p>
                    @error('description')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="mt-12">
            <h2 class="{{ $section }}">Thời gian</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="{{ $label }}">Ngày bắt đầu {!! $req !!}</label>
                    <input type="text" name="start_date" id="fp-start-date" required value="{{ old('start_date') }}"
                           class="{{ $field('start_date') }} fp-init" placeholder="DD/MM/YYYY">
                    @error('start_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $label }}">Ngày kết thúc {!! $req !!}</label>
                    <input type="text" name="end_date" id="fp-end-date" required value="{{ old('end_date') }}"
                           class="{{ $field('end_date') }} fp-init" placeholder="DD/MM/YYYY">
                    @error('end_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $label }}">Giờ bắt đầu</label>
                    <input type="time" name="start_time" value="{{ old('start_time') }}" class="{{ $field('start_time') }}">
                    <p class="{{ $help }}">Bỏ trống nếu sự kiện diễn ra cả ngày.</p>
                    @error('start_time')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $label }}">Giờ kết thúc</label>
                    <input type="time" name="end_time" value="{{ old('end_time') }}" class="{{ $field('end_time') }}">
                    @error('end_time')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="mt-12">
            <h2 class="{{ $section }}">Địa điểm</h2>
            <div class="space-y-6">
                <div class="flex flex-wrap gap-8">
                    <label class="{{ $choice }}">
                        <input type="radio" name="location_type" value="physical" x-model="locationType" class="{{ $radio }}" {{ old('location_type', 'physical') === 'physical' ? 'checked' : '' }}>
                        Trực tiếp
                    </label>
                    <label class="{{ $choice }}">
                        <input type="radio" name="location_type" value="online" x-model="locationType" class="{{ $radio }}" {{ old('location_type') === 'online' ? 'checked' : '' }}>
                        Trực tuyến
                    </label>
                </div>

                <div x-show="locationType === 'physical'" x-cloak class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label class="{{ $label }}">Tên địa điểm {!! $req !!}</label>
                            <input type="text" name="venue_name" value="{{ old('venue_name') }}" class="{{ $field('venue_name') }}" placeholder="Ví dụ: Nhà văn hoá Thanh niên">
                            @error('venue_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $label }}">Địa chỉ {!! $req !!}</label>
                            <input type="text" name="venue_address" value="{{ old('venue_address') }}" class="{{ $field('venue_address') }}" placeholder="Số nhà, tên đường">
                            @error('venue_address')<p class="{{ $err }}">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <x-address-picker
                        :required="false"
                        instance-id="event-venue"
                        name-province="province_code"
                        name-ward="ward_code"
                        :province-value="old('province_code')"
                        :ward-value="old('ward_code')"
                    />
                </div>

                <div x-show="locationType === 'online'" x-cloak>
                    <label class="{{ $label }}">Link tham gia {!! $req !!}</label>
                    <input type="url" name="online_url" value="{{ old('online_url') }}" class="{{ $field('online_url') }}" placeholder="https://meet.google.com/...">
                    @error('online_url')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $label }}">Website / Vé {!! $req !!}</label>
                    <input type="url" name="website_url" value="{{ old('website_url') }}" required class="{{ $field('website_url') }}" placeholder="https://">
                    <p class="{{ $help }}">Link mua vé/đăng ký, hoặc website chính thức của sự kiện.</p>
                    @error('website_url')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="mt-12">
            <h2 class="{{ $section }}">Giá vé</h2>
            <div class="space-y-6">
                <div class="flex flex-wrap gap-8">
                    <label class="{{ $choice }}">
                        <input type="radio" name="price_type" value="free" x-model="priceType" class="{{ $radio }}" {{ old('price_type', 'free') === 'free' ? 'checked' : '' }}>
                        Miễn phí
                    </label>
                    <label class="{{ $choice }}">
                        <input type="radio" name="price_type" value="single" x-model="priceType" class="{{ $radio }}" {{ old('price_type') === 'single' ? 'checked' : '' }}>
                        Giá cố định
                    </label>
                    <label class="{{ $choice }}">
                        <input type="radio" name="price_type" value="range" x-model="priceType" class="{{ $radio }}" {{ old('price_type') === 'range' ? 'checked' : '' }}>
                        Khoảng giá
                    </label>
                </div>

                <div x-show="priceType === 'single'" x-cloak class="md:w-1/2 md:pr-3">
                    <label class="{{ $label }}">Giá vé (VNĐ) {!! $req !!}</label>
                    <input type="number" name="price_amount" min="0" step="1000" value="{{ old('price_amount') }}" class="{{ $field('price_amount') }}">
                    @error('price_amount')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>

                <div x-show="priceType === 'range'" x-cloak class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="{{ $label }}">Từ (VNĐ) {!! $req !!}</label>
                        <input type="number" name="price_min" min="0" step="1000" value="{{ old('price_min') }}" class="{{ $field('price_min') }}">
                        @error('price_min')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Đến (VNĐ) {!! $req !!}</label>
                        <input type="number" name="price_max" min="0" step="1000" value="{{ old('price_max') }}" class="{{ $field('price_max') }}">
                        @error('price_max')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-12">
            <h2 class="{{ $section }}">Poster</h2>
            <label class="{{ $label }}">Ảnh poster {!! $req !!}</label>
            <input type="file" name="poster" accept="image/jpeg,image/png" required
                   class="{{ $field('poster') }} cursor-pointer p-0 file:mr-4 file:cursor-pointer file:border-0 file:bg-gray-900 file:px-5 file:py-3 file:text-xs file:font-bold file:uppercase file:tracking-widest file:text-white hover:file:bg-primary">
            <p class="{{ $help }}">JPG hoặc PNG, tối đa 1MB, khuyến nghị 1400×1000 (ngang).</p>
            @error('poster')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </section>

        <section class="mt-12">
            <h2 class="{{ $section }}">Thông tin của bạn</h2>
            <div class="space-y-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="{{ $label }}">Họ {!! $req !!}</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required maxlength="100" class="{{ $field('last_name') }}">
                        @error('last_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Tên {!! $req !!}</label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required maxlength="100" class="{{ $field('first_name') }}">
                        @error('first_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $label }}">Email {!! $req !!}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" class="{{ $field('email') }}">
                    <p class="{{ $help }}">Chỉ dùng để liên hệ khi cần — sẽ KHÔNG hiển thị công khai.</p>
                    @error('email')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="flex cursor-pointer select-none items-start gap-3 text-sm leading-relaxed text-gray-700">
                        <input type="checkbox" name="newsletter_consent" value="1" required
                               class="mt-0.5 h-4 w-4 shrink-0 accent-primary" {{ old('newsletter_consent') ? 'checked' : '' }}>
                        <span>Bằng việc gửi sự kiện, tôi đồng ý được thêm vào danh sách nhận bản tin. Bạn có thể huỷ đăng ký bất kỳ lúc nào. {!! $req !!}</span>
                    </label>
                    @error('newsletter_consent')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        @if(\Modules\Event\Features\PublicSubmission\Http\Middleware\ValidateEventTurnstile::isActive())
        <div class="mt-12 flex flex-col gap-1">
            <x-turnstile class="w-full" />
            @error('cf-turnstile-response')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        @endif

        <div class="mt-12 border-t border-gray-200 pt-10 text-center">
            <button type="submit"
                    class="w-full rounded-none bg-primary px-12 py-4 text-sm font-bold uppercase tracking-widest text-white transition hover:bg-primary/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 sm:w-auto">
                Gửi sự kiện
            </button>
            <p class="mt-4 text-xs text-gray-500">{!! $req !!} là trường bắt buộc</p>
        </div>
    </form>
</div>
</div>
@endsection

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'Modules/Event/resources/assets/js/event-public.js',
    ], 'build/frontend')
    @if(\Modules\Event\Features\PublicSubmission\Http\Middleware\ValidateEventTurnstile::isActive())
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
@endpush
