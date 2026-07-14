@extends('layouts.frontend')

@section('title', 'Gửi Sự Kiện')
@section('meta_description', 'Chia sẻ sự kiện dành cho gia đình và trẻ em lên cổng thông tin — miễn phí, chỉ cần điền form.')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">

    <div class="text-center mb-8">
        <span class="inline-block rounded-full bg-primary/10 px-6 py-2 font-black text-xs uppercase tracking-widest text-primary">Gửi Sự Kiện</span>
        <h1 class="mt-4 font-black text-3xl text-secondary">Chia Sẻ Sự Kiện Của Bạn</h1>
        <p class="mt-2 text-sm text-base-content/60">Sự kiện sẽ được đội ngũ biên tập xem xét trước khi hiển thị công khai. Email của bạn sẽ KHÔNG hiển thị ở bất kỳ đâu.</p>
    </div>

    @if($errors->any())
    <div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
            <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('event.public.submit.store') }}" enctype="multipart/form-data"
          data-event-submit-form
          x-data="{ locationType: '{{ old('location_type', 'physical') }}', priceType: '{{ old('price_type', 'free') }}' }">
        @csrf

        <div class="space-y-5">

            {{-- ── Thông tin cơ bản ─────────────────────────────────────── --}}
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body space-y-4">
                    <h2 class="card-title text-base">Thông tin sự kiện</h2>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Danh mục <span class="text-error">*</span></span></label>
                        <select name="category_id" required class="select select-bordered select-sm w-full ts-init">
                            <option value=""></option>
                            @foreach($eventCategories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @foreach($cat->children as $child)
                                <option value="{{ $child->id }}" {{ old('category_id') == $child->id ? 'selected' : '' }}>&nbsp;&nbsp;— {{ $child->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        @error('category_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tiêu đề <span class="text-error">*</span></span></label>
                        <input type="text" name="title" value="{{ old('title') }}" required maxlength="150"
                               class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                               placeholder="Không viết hoa toàn bộ">
                        <p class="text-xs text-base-content/40 mt-1">Vui lòng không viết hoa toàn bộ tiêu đề.</p>
                        @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control" x-data="{ len: {{ strlen(old('short_title', '')) }} }">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tiêu đề rút gọn <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs" :class="len > 55 ? 'text-error' : 'text-base-content/40'" x-text="len + ' / 55 ký tự'"></span>
                        </label>
                        <input type="text" name="short_title" value="{{ old('short_title') }}" required maxlength="55"
                               x-on:input="len = $event.target.value.length"
                               class="input input-bordered input-sm w-full @error('short_title') input-error @enderror">
                        @error('short_title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Mô tả <span class="text-error">*</span></span></label>
                        <textarea name="description" rows="5" required
                                  class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                                  placeholder="Không chèn liên kết — hệ thống sẽ coi đây là spam và từ chối.">{{ old('description') }}</textarea>
                        <p class="text-xs text-base-content/40 mt-1">Không chèn liên kết (http://, https://, www.) trong mô tả.</p>
                        @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- ── Thời gian ─────────────────────────────────────────────── --}}
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body space-y-4">
                    <h2 class="card-title text-base">Thời gian</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ngày bắt đầu <span class="text-error">*</span></span></label>
                            <input type="text" name="start_date" id="fp-start-date" required value="{{ old('start_date') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('start_date') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('start_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ngày kết thúc <span class="text-error">*</span></span></label>
                            <input type="text" name="end_date" id="fp-end-date" required value="{{ old('end_date') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('end_date') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('end_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Giờ bắt đầu</span>
                                <span class="label-text-alt text-xs text-base-content/40">Bỏ trống = cả ngày</span>
                            </label>
                            <input type="time" name="start_time" value="{{ old('start_time') }}"
                                   class="input input-bordered input-sm w-full @error('start_time') input-error @enderror">
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Giờ kết thúc</span></label>
                            <input type="time" name="end_time" value="{{ old('end_time') }}"
                                   class="input input-bordered input-sm w-full @error('end_time') input-error @enderror">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Địa điểm ──────────────────────────────────────────────── --}}
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body space-y-4">
                    <h2 class="card-title text-base">Địa điểm</h2>

                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="location_type" value="physical" x-model="locationType" class="radio radio-sm radio-primary" {{ old('location_type', 'physical') === 'physical' ? 'checked' : '' }}>
                            <span class="text-sm font-medium">Trực tiếp</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="location_type" value="online" x-model="locationType" class="radio radio-sm radio-primary" {{ old('location_type') === 'online' ? 'checked' : '' }}>
                            <span class="text-sm font-medium">Trực tuyến</span>
                        </label>
                    </div>

                    <div x-show="locationType === 'physical'" x-cloak class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tên địa điểm <span class="text-error">*</span></span></label>
                                <input type="text" name="venue_name" value="{{ old('venue_name') }}"
                                       class="input input-bordered input-sm w-full @error('venue_name') input-error @enderror">
                                @error('venue_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Địa chỉ <span class="text-error">*</span></span></label>
                                <input type="text" name="venue_address" value="{{ old('venue_address') }}"
                                       class="input input-bordered input-sm w-full @error('venue_address') input-error @enderror">
                                @error('venue_address')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
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

                    <div x-show="locationType === 'online'" x-cloak class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Link tham gia <span class="text-error">*</span></span></label>
                        <input type="url" name="online_url" value="{{ old('online_url') }}"
                               class="input input-bordered input-sm w-full @error('online_url') input-error @enderror"
                               placeholder="https://meet.google.com/...">
                        @error('online_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Website / Vé <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">Link mua vé/đăng ký, hoặc website chính thức</span>
                        </label>
                        <input type="url" name="website_url" value="{{ old('website_url') }}" required
                               class="input input-bordered input-sm w-full @error('website_url') input-error @enderror"
                               placeholder="https://">
                        @error('website_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- ── Giá vé ────────────────────────────────────────────────── --}}
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body space-y-4">
                    <h2 class="card-title text-base">Giá vé</h2>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="price_type" value="free" x-model="priceType" class="radio radio-sm radio-primary" {{ old('price_type', 'free') === 'free' ? 'checked' : '' }}>
                            <span class="text-sm font-medium">Miễn phí</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="price_type" value="single" x-model="priceType" class="radio radio-sm radio-primary" {{ old('price_type') === 'single' ? 'checked' : '' }}>
                            <span class="text-sm font-medium">Giá cố định</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="price_type" value="range" x-model="priceType" class="radio radio-sm radio-primary" {{ old('price_type') === 'range' ? 'checked' : '' }}>
                            <span class="text-sm font-medium">Khoảng giá</span>
                        </label>
                    </div>

                    <div x-show="priceType === 'single'" x-cloak class="form-control sm:w-64">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Giá vé (VNĐ) <span class="text-error">*</span></span></label>
                        <input type="number" name="price_amount" min="0" step="1000" value="{{ old('price_amount') }}"
                               class="input input-bordered input-sm w-full @error('price_amount') input-error @enderror">
                        @error('price_amount')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div x-show="priceType === 'range'" x-cloak class="grid grid-cols-2 gap-4 sm:w-96">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Từ (VNĐ) <span class="text-error">*</span></span></label>
                            <input type="number" name="price_min" min="0" step="1000" value="{{ old('price_min') }}"
                                   class="input input-bordered input-sm w-full @error('price_min') input-error @enderror">
                            @error('price_min')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Đến (VNĐ) <span class="text-error">*</span></span></label>
                            <input type="number" name="price_max" min="0" step="1000" value="{{ old('price_max') }}"
                                   class="input input-bordered input-sm w-full @error('price_max') input-error @enderror">
                            @error('price_max')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Poster ────────────────────────────────────────────────── --}}
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body space-y-4">
                    <h2 class="card-title text-base">Poster</h2>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ảnh poster <span class="text-error">*</span></span></label>
                        <input type="file" name="poster" accept="image/jpeg,image/png" required
                               class="file-input file-input-bordered file-input-sm w-full @error('poster') file-input-error @enderror">
                        <p class="text-xs text-base-content/40 mt-1">JPG hoặc PNG, tối đa 1MB, khuyến nghị 1400×1000 (ngang).</p>
                        @error('poster')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- ── Thông tin liên hệ ────────────────────────────────────── --}}
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body space-y-4">
                    <h2 class="card-title text-base">Thông tin của bạn</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Họ <span class="text-error">*</span></span></label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required maxlength="100"
                                   class="input input-bordered input-sm w-full @error('last_name') input-error @enderror">
                            @error('last_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tên <span class="text-error">*</span></span></label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required maxlength="100"
                                   class="input input-bordered input-sm w-full @error('first_name') input-error @enderror">
                            @error('first_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Email <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">Sẽ KHÔNG hiển thị công khai</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" required maxlength="255"
                               class="input input-bordered input-sm w-full @error('email') input-error @enderror">
                        @error('email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-start gap-2.5 cursor-pointer select-none">
                        <input type="checkbox" name="newsletter_consent" value="1" required
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0" {{ old('newsletter_consent') ? 'checked' : '' }}>
                        <span class="text-sm">
                            Bằng việc gửi sự kiện, tôi đồng ý được thêm vào danh sách nhận bản tin. Bạn có thể huỷ đăng ký bất kỳ lúc nào. <span class="text-error">*</span>
                        </span>
                    </label>
                    @error('newsletter_consent')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- ── CAPTCHA ───────────────────────────────────────────────── --}}
            @if(\Modules\Event\Features\PublicSubmission\Http\Middleware\ValidateEventTurnstile::isActive())
            <div class="flex flex-col gap-1">
                <x-turnstile class="w-full" />
                @error('cf-turnstile-response')<p class="text-error text-xs">{{ $message }}</p>@enderror
            </div>
            @endif

            <div class="text-center">
                <button type="submit" class="btn btn-primary px-10">Gửi Sự Kiện</button>
                <p class="text-center text-xs text-base-content/40 mt-3">
                    <span class="text-error">*</span> là trường bắt buộc
                </p>
            </div>

        </div>
    </form>
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
