@extends('realestate::public.anland.layout')

@section('title', 'Anland — Kênh thông tin nhà đất')
@section('meta_description', 'Anland — tra cứu tin đăng bán/cho thuê nhà đất đã qua kiểm duyệt, minh bạch pháp lý, cập nhật theo khu vực.')

@section('content')

{{-- ── Hero + search widget ── Giao diện chuyển thể từ spec/thu-vien-nha-dat.html: gradient
     xanh dương, thẻ tìm kiếm nền slate bo tròn, tab Mua bán/Cho thuê. ─────────────────────── --}}
<section class="anland-hero text-white relative overflow-hidden"
         x-data="anlandSearch('sale', @js(route('real-estate.public.sale.index')), @js(route('real-estate.public.rent.index')))">
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-6 right-16 w-56 h-7 bg-white/15 rotate-[36deg] rounded-full"></div>
        <div class="absolute top-24 -right-10 w-64 h-7 bg-white/10 rotate-[36deg] rounded-full"></div>
        <div class="absolute bottom-10 -left-10 w-56 h-6 bg-white/10 rotate-[36deg] rounded-full"></div>
        <div class="absolute bottom-24 left-6 w-40 h-6 rounded-full rotate-[36deg]" style="background:var(--az-teal); opacity:.45"></div>
    </div>

    <div class="anland-container relative py-10 lg:py-16">
        <h1 class="text-2xl lg:text-4xl font-bold mb-3 max-w-2xl text-pretty">Tìm nhà đất nhanh, minh bạch pháp lý</h1>
        <p class="text-white/80 mb-5 max-w-2xl">Tin đăng đã qua kiểm duyệt nội dung trước khi hiển thị công khai — an tâm tra cứu, không tin ảo.</p>

        @if($totalListings > 0)
        <div class="flex flex-wrap items-center gap-2 mb-8">
            <span class="rounded-full px-4 py-1.5 text-[13px] font-semibold bg-white/15 border border-white/20">
                {{ number_format($totalListings) }} tin đăng đã kiểm duyệt
            </span>
            <span class="rounded-full px-4 py-1.5 text-[13px] font-semibold bg-white/15 border border-white/20">
                Cập nhật theo khu vực mỗi ngày
            </span>
        </div>
        @endif

        <div class="bg-base-100 rounded-2xl shadow-xl overflow-hidden max-w-3xl">
            <div class="anland-search-tabs flex items-center gap-6 sm:gap-8 px-5 sm:px-7 pt-4 text-[14px] sm:text-[15px] font-semibold overflow-x-auto whitespace-nowrap">
                <button type="button" class="relative pb-3" :class="tab === 'sale' ? 'text-base-content' : 'text-base-content/40'" @click="tab = 'sale'">
                    Mua bán
                    <span x-show="tab === 'sale'" class="absolute left-0 right-0 -bottom-px h-[3px] rounded-full" style="background:var(--az-green)"></span>
                </button>
                <button type="button" class="relative pb-3" :class="tab === 'rent' ? 'text-base-content' : 'text-base-content/40'" @click="tab = 'rent'">
                    Cho thuê
                    <span x-show="tab === 'rent'" class="absolute left-0 right-0 -bottom-px h-[3px] rounded-full" style="background:var(--az-green)"></span>
                </button>
            </div>

            <form method="GET" :action="formAction" class="p-4 sm:p-5 grid sm:grid-cols-2 gap-3">
                <label class="flex items-center gap-2 bg-base-200 rounded-full px-4 py-2.5 text-sm font-medium text-base-content/70">
                    <svg viewBox="0 0 24 24" fill="var(--az-green)" class="w-4 h-4 shrink-0"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                    <select name="property_type" class="anland-select-pill flex-1 min-w-0 bg-transparent outline-none text-sm font-medium text-base-content/80">
                        <option value="">Loại hình</option>
                        @foreach($propertyTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="flex items-center gap-2 bg-base-200 rounded-full px-4 py-2.5 text-sm font-medium text-base-content/70">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--az-green)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0"><path d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    <select name="province_code" class="anland-select-pill flex-1 min-w-0 bg-transparent outline-none text-sm font-medium text-base-content/80">
                        <option value="">Toàn quốc</option>
                        @foreach($provinces as $province)
                        <option value="{{ $province->province_code }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                </label>

                <input type="number" name="price_min" placeholder="Giá từ (VNĐ)" class="bg-base-200 rounded-full px-4 py-2.5 text-sm font-medium text-base-content/80 outline-none w-full">
                <input type="number" name="price_max" placeholder="Giá đến (VNĐ)" class="bg-base-200 rounded-full px-4 py-2.5 text-sm font-medium text-base-content/80 outline-none w-full">

                <button type="submit" class="btn btn-accent rounded-full sm:col-span-2">Tìm kiếm</button>
            </form>
        </div>
    </div>
</section>

{{-- ── Category tiles ────────────────────────────────────────────────────── --}}
<section class="anland-container py-10">
    <h2 class="anland-section-title text-xl sm:text-2xl font-bold text-base-content mb-6">Khám phá theo loại hình</h2>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($propertyTypes as $type)
        <a href="{{ route('real-estate.public.sale.index', ['property_type' => $type->value]) }}"
           class="anland-tile card border border-base-300 p-5 text-center">
            <div class="w-11 h-11 rounded-full flex items-center justify-center mx-auto mb-3" style="background:color-mix(in oklab, var(--az-navy) 8%, white)">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--az-navy)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                    @if($type->value === 'apartment')
                    <path d="M4 21V9l8-6 8 6v12"/><path d="M9 21v-5h6v5"/><path d="M9 12h.01M15 12h.01M12 9h.01"/>
                    @elseif($type->value === 'land')
                    <path d="M3 20l6-13 4 7 3-5 5 11z"/>
                    @elseif($type->value === 'layout')
                    <rect x="3" y="4" width="18" height="16" rx="1.5"/><path d="M3 10h18M9 10v10"/>
                    @else
                    <path d="M4 21V8l8-5 8 5v13"/><path d="M9 21v-6h6v6"/>
                    @endif
                </svg>
            </div>
            <p class="font-semibold text-sm">{{ $type->label() }}</p>
            <p class="text-xs text-base-content/50 mt-1">{{ $categoryCounts->get($type->value, 0) }} tin đăng</p>
        </a>
        @endforeach
    </div>
</section>

{{-- ── Featured listings ─────────────────────────────────────────────────── --}}
@if($featured->isNotEmpty())
<section class="anland-container py-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="anland-section-title text-xl sm:text-2xl font-bold text-base-content">Tin nổi bật</h2>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($featured as $listing)
        @include('realestate::public.anland.partials.listing-card', ['listing' => $listing])
        @endforeach
    </div>
</section>
@endif

{{-- ── Latest sale listings ──────────────────────────────────────────────── --}}
<section class="anland-container py-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="anland-section-title text-xl sm:text-2xl font-bold text-base-content">Nhà đất bán mới đăng</h2>
        <a href="{{ route('real-estate.public.sale.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold" style="color:var(--az-navy)">» Xem tất cả</a>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @forelse($latestSale as $listing)
        @include('realestate::public.anland.partials.listing-card', ['listing' => $listing])
        @empty
        <p class="col-span-full text-center text-base-content/40 py-10">Chưa có tin nào.</p>
        @endforelse
    </div>
</section>

{{-- ── Latest rent listings ──────────────────────────────────────────────── --}}
<section class="anland-container py-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="anland-section-title text-xl sm:text-2xl font-bold text-base-content">Nhà đất cho thuê mới đăng</h2>
        <a href="{{ route('real-estate.public.rent.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold" style="color:var(--az-navy)">» Xem tất cả</a>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @forelse($latestRent as $listing)
        @include('realestate::public.anland.partials.listing-card', ['listing' => $listing])
        @empty
        <p class="col-span-full text-center text-base-content/40 py-10">Chưa có tin nào.</p>
        @endforelse
    </div>
</section>

{{-- ── Vì sao chọn Anland ────────────────────────────────────────────────── --}}
<section class="bg-base-200 py-12 mt-6">
    <div class="anland-container grid sm:grid-cols-3 gap-8">
        <div class="text-center">
            <div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-white" style="background:var(--az-green)">1</div>
            <h3 class="font-semibold mb-1">Tin đã kiểm duyệt</h3>
            <p class="text-sm text-base-content/60">Mọi tin đăng đều qua quy trình duyệt nội dung trước khi hiển thị công khai.</p>
        </div>
        <div class="text-center">
            <div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-white" style="background:var(--az-green)">2</div>
            <h3 class="font-semibold mb-1">Thông tin minh bạch</h3>
            <p class="text-sm text-base-content/60">Diện tích, pháp lý, hướng nhà hiển thị đầy đủ theo từng loại hình bất động sản.</p>
        </div>
        <div class="text-center">
            <div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-white" style="background:var(--az-green)">3</div>
            <h3 class="font-semibold mb-1">Lọc theo khu vực</h3>
            <p class="text-sm text-base-content/60">Tìm nhanh theo tỉnh/thành, mức giá và loại hình phù hợp nhu cầu.</p>
        </div>
    </div>
</section>

{{-- ── Loan calculator tool ──────────────────────────────────────────────── --}}
<section class="anland-container py-10" x-data="anlandLoanCalculator">
    <h2 class="anland-section-title text-xl sm:text-2xl font-bold text-base-content mb-1">Công cụ tính khoản vay mua nhà</h2>
    <p class="text-sm text-base-content/60 mt-4 mb-5">Ước tính khoản trả góp hằng tháng — chỉ mang tính tham khảo, tính toán ngay trên trình duyệt của bạn.</p>

    <div class="card border border-base-300 bg-base-100">
        <div class="card-body grid lg:grid-cols-2 gap-8">
            <div class="space-y-5">
                <div>
                    <label class="label py-0 pb-1"><span class="label-text">Giá trị bất động sản</span></label>
                    <input type="range" min="200000000" max="20000000000" step="100000000" x-model.number="price" class="range range-sm anland-range">
                    <p class="text-sm font-semibold mt-1" x-text="formatVnd(price)"></p>
                </div>
                <div>
                    <label class="label py-0 pb-1"><span class="label-text">Vốn tự có (%)</span></label>
                    <input type="range" min="10" max="80" step="5" x-model.number="downPaymentPercent" class="range range-sm anland-range">
                    <p class="text-sm font-semibold mt-1" x-text="downPaymentPercent + '%'"></p>
                </div>
                <div>
                    <label class="label py-0 pb-1"><span class="label-text">Lãi suất vay/năm (%)</span></label>
                    <input type="range" min="4" max="16" step="0.5" x-model.number="annualRatePercent" class="range range-sm anland-range">
                    <p class="text-sm font-semibold mt-1" x-text="annualRatePercent + '%'"></p>
                </div>
                <div>
                    <label class="label py-0 pb-1"><span class="label-text">Thời hạn vay (năm)</span></label>
                    <input type="range" min="5" max="30" step="1" x-model.number="years" class="range range-sm anland-range">
                    <p class="text-sm font-semibold mt-1" x-text="years + ' năm'"></p>
                </div>
            </div>

            <div class="bg-base-200 rounded-box p-5 flex flex-col justify-center gap-4">
                <div>
                    <p class="text-xs text-base-content/50">Số tiền cần vay</p>
                    <p class="text-lg font-bold" x-text="formatVnd(loanAmount)"></p>
                </div>
                <div>
                    <p class="text-xs text-base-content/50">Trả góp hằng tháng (gốc + lãi)</p>
                    <p class="text-2xl font-bold" style="color:var(--az-price-blue)" x-text="formatVnd(monthlyPayment)"></p>
                </div>
                <div>
                    <p class="text-xs text-base-content/50">Tổng lãi phải trả</p>
                    <p class="text-lg font-bold" x-text="formatVnd(totalInterest)"></p>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
