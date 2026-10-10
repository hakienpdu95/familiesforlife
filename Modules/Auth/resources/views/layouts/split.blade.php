@extends('layouts.auth')

@section('body_class', 'bg-base-100 h-screen w-full overflow-hidden')

@section('content')
@php
    $slides = [
        ['title' => 'Hệ thống Quản trị Nội dung', 'text' => 'Quản lý nội dung, dữ liệu và quy trình làm việc của tổ chức trên một nền tảng duy nhất.'],
        ['title' => 'AI Studio tích hợp', 'text' => 'Khai thác ý tưởng, phân tích và sản xuất nội dung nhanh hơn với trợ lý AI.'],
        ['title' => 'Phân quyền linh hoạt', 'text' => 'Kiểm soát truy cập theo vai trò, an toàn cho mọi phòng ban trong tổ chức.'],
    ];
@endphp
<div class="grid h-screen w-full lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] xl:grid-cols-2">

    <aside
        class="relative hidden overflow-hidden bg-linear-to-br from-primary via-blue-700 to-blue-950 text-primary-content lg:flex lg:flex-col lg:items-center lg:justify-center lg:px-12"
        x-data="{ active: 0, total: {{ count($slides) }}, init() { setInterval(() => this.active = (this.active + 1) % this.total, 5000) } }"
    >
        <div class="pointer-events-none absolute -left-24 -top-24 size-80 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-20 size-96 rounded-full bg-white/10 blur-3xl"></div>

        <div class="relative flex w-full max-w-2xl flex-col items-center text-center">
            <img
                src="{{ asset('images/post-cover-placeholder.svg') }}"
                alt="Minh họa"
                width="640"
                height="480"
                class="mb-10 aspect-4/3 h-auto w-4/5 rounded-box object-contain"
            />

            <div class="grid w-full max-w-md">
                @foreach ($slides as $i => $slide)
                    <div
                        class="col-start-1 row-start-1 opacity-0 transition-opacity duration-500 data-[active=true]:opacity-100"
                        data-active="{{ $i === 0 ? 'true' : 'false' }}"
                        :data-active="active === {{ $i }}"
                        :aria-hidden="active !== {{ $i }}"
                    >
                        <h2 class="text-3xl font-bold leading-tight">{{ $slide['title'] }}</h2>
                        <p class="mt-3 text-base text-primary-content/75">{{ $slide['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="absolute bottom-10 flex gap-2">
            @foreach ($slides as $i => $slide)
                <button
                    type="button"
                    @click="active = {{ $i }}"
                    class="h-2 w-2 rounded-full bg-white/40 transition-all duration-300 hover:bg-white/70 data-[active=true]:w-6 data-[active=true]:bg-white"
                    data-active="{{ $i === 0 ? 'true' : 'false' }}"
                    :data-active="active === {{ $i }}"
                    aria-label="Slide {{ $i + 1 }}"
                ></button>
            @endforeach
        </div>
    </aside>

    <main class="flex h-screen items-center justify-center overflow-y-auto bg-base-100 px-4 py-10 sm:px-8">
        <div class="w-full max-w-md">

            <div class="mb-8">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" width="40" height="40" class="mb-6 h-10 w-auto lg:hidden" />
                <h1 class="text-3xl font-bold text-base-content">@yield('heading')</h1>
                <p class="mt-2 text-sm text-base-content/60">@yield('subheading')</p>
            </div>

            @if (session('status'))
                <div role="alert" class="alert alert-success alert-soft mb-5 text-sm">
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="alert alert-error alert-soft mb-5 text-sm">
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('form')

        </div>
    </main>
</div>
@endsection
