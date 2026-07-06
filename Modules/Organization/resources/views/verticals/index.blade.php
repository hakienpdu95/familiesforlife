@extends('layouts.backend')
@section('title', 'Dịch vụ triển khai — ' . $organization->name)

@section('content')
<div class="max-w-5xl">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-base-content/40 mb-1">
                <a href="{{ route('backend.organizations.show', $organization) }}" class="hover:text-primary">{{ $organization->name }}</a>
                <span>/</span>
                <span>Dịch vụ triển khai</span>
            </div>
            <h1 class="text-2xl font-bold text-base-content">Quản lý dịch vụ</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Kích hoạt hoặc tắt các vertical triển khai cho tổ chức này</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('backend.organizations.verticals.create', $organization) }}"
               class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tạo mới từ đầu
            </a>
            <a href="{{ route('backend.organizations.show', $organization) }}"
               class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Quay lại
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success mb-4 py-2.5 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Stats bar --}}
    @php
        $activeCount = $activated->where('status', 'active')->count();
        $totalCount  = $templates->count();
    @endphp
    <div class="flex items-center gap-4 bg-base-100 border border-base-200 rounded-xl px-5 py-3.5 mb-6 shadow-sm">
        <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-success"></div>
            <span class="text-sm font-semibold">{{ $activeCount }} đang hoạt động</span>
        </div>
        <div class="divider divider-horizontal m-0 h-4"></div>
        <span class="text-sm text-base-content/50">{{ $totalCount }} dịch vụ có sẵn trong catalog</span>
    </div>

    {{-- Vertical cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @foreach($templates as $tpl)
        @php
            $ov      = $activated->get($tpl->code);
            $isActive = $ov && $ov->status === 'active';

            $icon = match($tpl->code) {
                'traceability' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
                'nong-san'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'thuc-pham'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>',
                'truong-hoc'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/>',
                default        => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>',
            };

            $accentColor = $isActive ? 'border-success/30 bg-success/5' : 'border-base-200 bg-base-100';
        @endphp

        <div class="card border {{ $accentColor }} shadow-sm transition-all duration-200 hover:shadow-md">
            <div class="card-body p-5">

                {{-- Card header --}}
                <div class="flex items-start gap-3 mb-4">
                    <div class="flex-shrink-0 w-11 h-11 rounded-xl {{ $isActive ? 'bg-success/15' : 'bg-base-200' }} flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 {{ $isActive ? 'text-success' : 'text-base-content/40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            {!! $icon !!}
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-bold text-base leading-snug">{{ $tpl->label }}</h3>
                            @if($isActive)
                            <span class="badge badge-success badge-xs gap-1">
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-current"></span>
                                Đang hoạt động
                            </span>
                            @else
                            <span class="badge badge-ghost badge-xs">Chưa kích hoạt</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 mt-0.5">
                            <code class="text-xs text-base-content/40 font-mono">{{ $tpl->code }}</code>
                        </div>
                    </div>
                </div>

                {{-- Meta --}}
                <div class="grid grid-cols-2 gap-x-3 gap-y-2 text-xs mb-4">
                    <div class="flex items-center gap-1.5 text-base-content/60">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span>{{ $tpl->target_label }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-base-content/60">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span>{{ $tpl->phases->count() }} giai đoạn</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-base-content/60">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ count($tpl->default_roles ?? []) }} vai trò</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-base-content/60">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 10V5a2 2 0 012-2z"/>
                        </svg>
                        <span>{{ $tpl->has_physical_assets ? 'Thực địa' : 'Kỹ thuật số' }}</span>
                    </div>
                </div>

                {{-- Phases preview --}}
                <div class="flex flex-wrap gap-1 mb-4">
                    @foreach($tpl->phases->take(6) as $phase)
                    <span class="badge badge-outline badge-xs font-normal opacity-70">{{ $phase->label }}</span>
                    @endforeach
                    @if($tpl->phases->count() > 6)
                    <span class="badge badge-ghost badge-xs">+{{ $tpl->phases->count() - 6 }}</span>
                    @endif
                </div>

                {{-- Activated info --}}
                @if($isActive && $ov->activated_at)
                <div class="text-xs text-base-content/40 mb-4 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Kích hoạt {{ $ov->activated_at->diffForHumans() }}
                    @if($ov->activated_by)
                        bởi {{ \App\Models\User::find($ov->activated_by)?->name ?? 'hệ thống' }}
                    @endif
                </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center gap-2 pt-1 border-t border-base-200">
                    @if($isActive)
                    <a href="{{ route('backend.organizations.verticals.config', [$organization, $tpl->code]) }}"
                       class="btn btn-sm btn-ghost gap-1.5 flex-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        Cấu hình
                    </a>
                    <a href="{{ route('backend.organizations.verticals.preview', [$organization, $tpl->code]) }}"
                       class="btn btn-sm btn-ghost gap-1.5"
                       target="_blank">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Dashboard
                    </a>
                    <form method="POST"
                          action="{{ route('backend.organizations.verticals.deactivate', [$organization, $tpl->code]) }}"
                          x-data
                          x-on:submit.prevent="if(confirm('Tắt dịch vụ này? Dữ liệu hiện có sẽ không bị xóa.')) $el.submit()">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-ghost text-error gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            Tắt
                        </button>
                    </form>
                    @else
                    <form method="POST"
                          action="{{ route('backend.organizations.verticals.activate', [$organization, $tpl->code]) }}"
                          class="flex-1">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary w-full gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Nhân bản từ thư viện
                        </button>
                    </form>
                    @endif
                </div>

            </div>
        </div>
        @endforeach
    </div>

    {{-- Vertical tự tạo từ đầu — không nằm trong catalog thư viện --}}
    @if($custom->isNotEmpty())
    <div class="mt-8">
        <div class="flex items-center gap-2 mb-3">
            <h2 class="font-bold text-sm text-base-content/70 uppercase tracking-wide">Tự tạo từ đầu</h2>
            <span class="badge badge-ghost badge-xs">{{ $custom->count() }}</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach($custom as $ov)
            @php $isActive = $ov->status === 'active'; @endphp
            <div class="card border {{ $isActive ? 'border-success/30 bg-success/5' : 'border-base-200 bg-base-100' }} shadow-sm transition-all duration-200 hover:shadow-md">
                <div class="card-body p-5">

                    <div class="flex items-start gap-3 mb-4">
                        <div class="flex-shrink-0 w-11 h-11 rounded-xl {{ $isActive ? 'bg-success/15' : 'bg-base-200' }} flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 {{ $isActive ? 'text-success' : 'text-base-content/40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-bold text-base leading-snug">{{ $ov->label }}</h3>
                                @if($isActive)
                                <span class="badge badge-success badge-xs gap-1">
                                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-current"></span>
                                    Đang hoạt động
                                </span>
                                @else
                                <span class="badge badge-ghost badge-xs">Đã tắt</span>
                                @endif
                            </div>
                            <code class="text-xs text-base-content/40 font-mono">{{ $ov->code }}</code>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 text-xs text-base-content/50 mb-4">
                        <span>{{ $ov->phases->count() }} giai đoạn</span>
                        <span>&middot;</span>
                        <span>{{ $ov->target_label }}</span>
                    </div>

                    <div class="flex items-center gap-2 pt-1 border-t border-base-200">
                        <a href="{{ route('backend.organizations.verticals.config', [$organization, $ov->code]) }}"
                           class="btn btn-sm btn-ghost gap-1.5 flex-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Cấu hình / Builder
                        </a>
                        @if($isActive)
                        <a href="{{ route('backend.organizations.verticals.preview', [$organization, $ov->code]) }}"
                           class="btn btn-sm btn-ghost gap-1.5"
                           target="_blank">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Dashboard
                        </a>
                        <form method="POST"
                              action="{{ route('backend.organizations.verticals.deactivate', [$organization, $ov->code]) }}"
                              x-data
                              x-on:submit.prevent="if(confirm('Tắt dịch vụ này? Dữ liệu hiện có sẽ không bị xóa.')) $el.submit()">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-ghost text-error gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                Tắt
                            </button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('backend.organizations.verticals.activate', [$organization, $ov->code]) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Bật lại
                            </button>
                        </form>
                        @endif
                    </div>

                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
