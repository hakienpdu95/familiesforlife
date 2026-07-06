@extends('layouts.backend')
@section('title', 'Thư viện mẫu Vertical')

@section('content')
<div class="max-w-5xl">

    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Thư viện mẫu Vertical</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Bản mẫu dùng chung — tổ chức nhân bản hoặc tạo mới độc lập từ đây</p>
        </div>
        <a href="{{ route('backend.vertical-templates.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm bản mẫu
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success mb-4 py-2.5 text-sm">{{ session('success') }}</div>
    @endif

    @if($templates->isEmpty())
    <div class="text-center py-16 border-2 border-dashed border-base-300 rounded-2xl bg-base-100">
        <p class="font-semibold text-base-content/40">Chưa có bản mẫu nào trong thư viện</p>
        <p class="text-xs text-base-content/30 mt-1">Nhấn "Thêm bản mẫu" để tạo bản mẫu đầu tiên</p>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @foreach($templates as $tpl)
        <div class="card border border-base-200 bg-base-100 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div class="min-w-0">
                        <h3 class="font-bold text-base leading-snug truncate">{{ $tpl->label }}</h3>
                        <code class="text-xs text-base-content/40 font-mono">{{ $tpl->code }}</code>
                    </div>
                    @if($tpl->is_active)
                    <span class="badge badge-success badge-xs shrink-0">Đang dùng</span>
                    @else
                    <span class="badge badge-ghost badge-xs shrink-0">Tắt</span>
                    @endif
                </div>

                <div class="flex items-center gap-3 text-xs text-base-content/50 mb-4">
                    <span>{{ $tpl->phases_count }} phase</span>
                    <span>&middot;</span>
                    <span>{{ $tpl->clones_count }} tổ chức đang dùng</span>
                </div>

                <div class="flex items-center gap-2 pt-3 border-t border-base-200">
                    <a href="{{ route('backend.vertical-templates.edit', $tpl) }}"
                       class="btn btn-sm btn-ghost gap-1.5 flex-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Sửa / Builder
                    </a>
                    <form method="POST" action="{{ route('backend.vertical-templates.destroy', $tpl) }}"
                          x-data x-on:submit.prevent="if(confirm('Xóa bản mẫu &quot;{{ $tpl->label }}&quot;? Các tổ chức đã nhân bản không bị ảnh hưởng.')) $el.submit()">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-ghost text-error gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Xóa
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
