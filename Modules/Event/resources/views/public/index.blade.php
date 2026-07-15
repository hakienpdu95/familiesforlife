@extends('layouts.frontend')

@section('title', $search ? "Tìm sự kiện: {$search}" : 'Sự Kiện')
@section('meta_description', 'Sự kiện, hoạt động vui chơi và trải nghiệm cho gia đình — cập nhật liên tục.')

@push('meta')
<link rel="canonical" href="{{ route('event.public.home') }}">
@endpush

@section('content')

@if($eventCategories->isNotEmpty())
<div class="flex text-white text-sm font-black uppercase tracking-wide text-center">
    @php $palette = ['bg-secondary', 'bg-primary', 'bg-warning']; @endphp
    @foreach($eventCategories->take(3) as $i => $cat)
    <a href="{{ route('event.public.category', ['category' => $cat->slug]) }}"
       class="flex-1 py-4 {{ $palette[$i % count($palette)] }} hover:opacity-90">{{ $cat->name }}</a>
    @endforeach
</div>
@endif

<div class="container">
    <div class="py-10">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <h1 class="text-2xl font-bold text-base-content">
                {{ $search ? "Kết quả tìm kiếm: “{$search}”" : 'Sự Kiện Sắp Diễn Ra' }}
            </h1>
            <a href="{{ route('event.public.submit.form') }}" class="btn btn-sm border-none bg-secondary text-white hover:bg-secondary/90">Gửi Sự Kiện Của Bạn</a>
        </div>

        <form method="GET" class="mb-6">
            <input type="text" name="q" value="{{ $search }}" placeholder="Tìm sự kiện..."
                   class="input input-bordered input-sm w-full sm:w-72">
        </form>

        <x-frontend.event-grid :events="$events" />
    </div>
</div>
@endsection
