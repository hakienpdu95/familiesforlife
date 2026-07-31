@extends('realestate::public.anland.layout')

@section('title', 'Anland — Kênh thông tin nhà đất')
@section('meta_description', 'Anland — tra cứu tin đăng bán/cho thuê nhà đất đã qua kiểm duyệt, minh bạch pháp lý, cập nhật theo khu vực.')

@section('content')

{{-- ── Hero + search widget ──────────────────────────────────────────────── --}}
<section class="anland-hero text-white"
         x-data="anlandSearch('sale', @js(route('real-estate.public.sale.index')), @js(route('real-estate.public.rent.index')))">
    <div class="anland-container py-10 lg:py-16">
        <h1 class="text-2xl lg:text-4xl font-bold mb-2">Tìm nhà đất nhanh, minh bạch pháp lý</h1>
        <p class="text-white/80 mb-8 max-w-2xl">Tin đăng đã qua kiểm duyệt nội dung trước khi hiển thị công khai — an tâm tra cứu, không tin ảo.</p>

        <div class="bg-base-100 rounded-box shadow-xl overflow-hidden max-w-3xl">
            <div class="anland-search-tabs tabs tabs-lifted px-2 pt-2 bg-base-200">
                <button type="button" class="tab" :class="tab === 'sale' ? 'tab-active bg-base-100 text-base-content font-semibold' : 'text-base-content/60'" @click="tab = 'sale'">Mua bán</button>
                <button type="button" class="tab" :class="tab === 'rent' ? 'tab-active bg-base-100 text-base-content font-semibold' : 'text-base-content/60'" @click="tab = 'rent'">Cho thuê</button>
            </div>

            <form method="GET" :action="formAction" class="p-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <select name="property_type" class="select select-bordered select-sm w-full text-base-content">
                    <option value="">Loại hình</option>
                    @foreach($propertyTypes as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>

                <select name="province_code" class="select select-bordered select-sm w-full text-base-content">
                    <option value="">Toàn quốc</option>
                    @foreach($provinces as $province)
                    <option value="{{ $province->province_code }}">{{ $province->name }}</option>
                    @endforeach
                </select>

                <input type="number" name="price_min" placeholder="Giá từ (VNĐ)" class="input input-bordered input-sm w-full text-base-content">
                <input type="number" name="price_max" placeholder="Giá đến (VNĐ)" class="input input-bordered input-sm w-full text-base-content">

                <button type="submit" class="btn btn-primary btn-sm sm:col-span-2 lg:col-span-4">Tìm kiếm</button>
            </form>
        </div>
    </div>
</section>

{{-- ── Category tiles ────────────────────────────────────────────────────── --}}
<section class="anland-container py-10">
    <h2 class="text-xl font-bold mb-4">Khám phá theo loại hình</h2>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($propertyTypes as $type)
        <a href="{{ route('real-estate.public.sale.index', ['property_type' => $type->value]) }}"
           class="anland-tile card border border-base-300 p-4 text-center">
            <p class="font-semibold">{{ $type->label() }}</p>
            <p class="text-xs text-base-content/50 mt-1">{{ $categoryCounts->get($type->value, 0) }} tin đăng</p>
        </a>
        @endforeach
    </div>
</section>

{{-- ── Featured listings ─────────────────────────────────────────────────── --}}
@if($featured->isNotEmpty())
<section class="anland-container py-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold">Tin nổi bật</h2>
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
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold">Nhà đất bán mới đăng</h2>
        <a href="{{ route('real-estate.public.sale.index') }}" class="link link-primary text-sm">Xem tất cả</a>
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
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold">Nhà đất cho thuê mới đăng</h2>
        <a href="{{ route('real-estate.public.rent.index') }}" class="link link-primary text-sm">Xem tất cả</a>
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
<section class="bg-base-200 py-10 mt-6">
    <div class="anland-container grid sm:grid-cols-3 gap-6">
        <div class="text-center">
            <div class="w-12 h-12 rounded-full bg-primary text-primary-content flex items-center justify-center mx-auto mb-3 font-bold">1</div>
            <h3 class="font-semibold mb-1">Tin đã kiểm duyệt</h3>
            <p class="text-sm text-base-content/60">Mọi tin đăng đều qua quy trình duyệt nội dung trước khi hiển thị công khai.</p>
        </div>
        <div class="text-center">
            <div class="w-12 h-12 rounded-full bg-primary text-primary-content flex items-center justify-center mx-auto mb-3 font-bold">2</div>
            <h3 class="font-semibold mb-1">Thông tin minh bạch</h3>
            <p class="text-sm text-base-content/60">Diện tích, pháp lý, hướng nhà hiển thị đầy đủ theo từng loại hình bất động sản.</p>
        </div>
        <div class="text-center">
            <div class="w-12 h-12 rounded-full bg-primary text-primary-content flex items-center justify-center mx-auto mb-3 font-bold">3</div>
            <h3 class="font-semibold mb-1">Lọc theo khu vực</h3>
            <p class="text-sm text-base-content/60">Tìm nhanh theo tỉnh/thành, mức giá và loại hình phù hợp nhu cầu.</p>
        </div>
    </div>
</section>

{{-- ── Loan calculator tool ──────────────────────────────────────────────── --}}
<section class="anland-container py-10" x-data="anlandLoanCalculator">
    <h2 class="text-xl font-bold mb-1">Công cụ tính khoản vay mua nhà</h2>
    <p class="text-sm text-base-content/60 mb-5">Ước tính khoản trả góp hằng tháng — chỉ mang tính tham khảo, tính toán ngay trên trình duyệt của bạn.</p>

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
                    <p class="text-2xl font-bold text-primary" x-text="formatVnd(monthlyPayment)"></p>
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
