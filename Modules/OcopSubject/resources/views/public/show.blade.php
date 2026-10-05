@extends('layouts.frontend')

@section('title', $subject->name)
@section('meta_description', \Illuminate\Support\Str::limit(trim(strip_tags((string) $subject->story)) ?: $subject->name.' — chủ thể sản phẩm OCOP '.$subject->province_name, 160))

@section('content')
<div class="container">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start"
         x-data="{ activeTab: ['products', 'story', 'certificates'].includes(location.hash.slice(1)) ? location.hash.slice(1) : 'products' }"
         x-init="$watch('activeTab', t => history.replaceState(null, '', '#' + t))">

        <aside class="lg:col-span-3 space-y-4">
            @include('ocopsubject::public.partials._sidebar')
        </aside>

        <main class="lg:col-span-9 min-w-0">

            <h1 class="text-2xl lg:text-3xl font-bold uppercase leading-tight text-[#117a3a]">{{ $subject->name }}</h1>
            @if($subject->name_en)
            <p class="mt-1 text-sm text-base-content/60">{{ $subject->name_en }}</p>
            @endif

            @php
                $tabs = [
                    'products' => 'Sản phẩm',
                    'story' => 'Câu chuyện',
                    'certificates' => 'Giấy chứng nhận',
                ];
            @endphp
            <div id="products" class="mt-5 flex scroll-mt-24 overflow-x-auto border-b border-[#e0e0e0]" role="tablist">
                @foreach($tabs as $key => $label)
                <button type="button" role="tab" :aria-selected="activeTab === '{{ $key }}'"
                        @click="activeTab = '{{ $key }}'"
                        class="-mb-px whitespace-nowrap border-b-2 px-5 py-3 text-sm font-bold uppercase tracking-wide transition-colors"
                        :class="activeTab === '{{ $key }}' ? 'border-[#117a3a] text-[#117a3a]' : 'border-transparent text-[#555] hover:text-[#117a3a]'">
                    {{ $label }}
                    @if($key === 'products')
                    <span class="ml-1 text-xs font-normal">({{ $products->total() }})</span>
                    @endif
                </button>
                @endforeach
            </div>

            <div class="pt-5">
                <section x-show="activeTab === 'products'" role="tabpanel">
                    @include('ocopsubject::public.partials._tab-products')
                </section>

                <section x-show="activeTab === 'story'" x-cloak role="tabpanel">
                    @include('ocopsubject::public.partials._tab-story')
                </section>

                <section x-show="activeTab === 'certificates'" x-cloak role="tabpanel">
                    @include('ocopsubject::public.partials._tab-certificates')
                </section>
            </div>
        </main>

    </div>
</div>
@endsection
