@extends('layouts.backend')
@section('title', 'Sự kiện')

@section('content')
<div x-data="{ rejectOpen: false, rejectAction: '', rejectTitle: '' }">

    @foreach(['success','error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition.opacity.duration.500ms
             class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Sự kiện</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Sự kiện độc giả nộp qua cổng thông tin, hoặc toà soạn/vận hành tự nhập trực tiếp</p>
        </div>
        <div class="flex items-center gap-2">
            @can('event_category.manage')
            <a href="{{ route('backend.event.categories.index') }}" class="btn btn-ghost btn-sm">Danh mục sự kiện</a>
            @endcan
            @can('create', \Modules\Event\Models\Event::class)
            <a href="{{ route('backend.event.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm sự kiện
            </a>
            @endcan
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tiêu đề sự kiện..."
               class="input input-bordered input-sm w-56">
        <select name="status" class="select select-bordered select-sm" onchange="this.form.submit()">
            <option value="">Tất cả trạng thái</option>
            @foreach($statuses as $s)
            <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('q') || request('status'))
        <a href="{{ route('backend.event.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Sự kiện</th>
                        <th>Danh mục</th>
                        <th>Thời gian</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="w-72"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($events as $event)
                <tr class="hover">
                    <td>
                        <span class="font-medium text-sm">{{ $event->title }}</span>
                        <div class="text-xs text-base-content/40 font-mono">{{ $event->slug }}</div>
                    </td>
                    <td class="text-sm text-base-content/60">
                        <span class="inline-block size-2.5 rounded-full mr-1.5 align-middle" style="background:{{ $event->category?->color_hex ?? '#94a3b8' }}"></span>
                        {{ $event->category?->name ?? '—' }}
                    </td>
                    <td class="text-sm text-base-content/60">
                        {{ $event->start_date?->format('d/m/Y') }} – {{ $event->end_date?->format('d/m/Y') }}
                    </td>
                    <td class="text-center">
                        <span class="badge badge-sm {{ $event->status->badgeClass() }}">{{ $event->status->label() }}</span>
                    </td>
                    <td>
                        @php
                            $canApprove = $event->status->canTransitionTo(\Modules\Event\Enums\EventStatus::Approved);
                            $canReject  = $event->status->canTransitionTo(\Modules\Event\Enums\EventStatus::Rejected);
                            $canPublish = $event->status->canTransitionTo(\Modules\Event\Enums\EventStatus::Published);
                            $canArchive = $event->status->canTransitionTo(\Modules\Event\Enums\EventStatus::Archived);
                        @endphp
                        <div class="flex justify-end gap-1.5">
                            @can('approve', $event)
                            @if($canApprove)
                            <form method="POST" action="{{ route('backend.event.approve', $event) }}">
                                @csrf
                                <button class="btn btn-success btn-xs">Duyệt</button>
                            </form>
                            @endif
                            @endcan
                            @can('reject', $event)
                            @if($canReject)
                            <button type="button" class="btn btn-error btn-outline btn-xs"
                                    @click="rejectOpen = true; rejectAction = '{{ route('backend.event.reject', $event) }}'; rejectTitle = @js($event->title)">
                                Từ chối
                            </button>
                            @endif
                            @endcan
                            @can('publish', $event)
                            @if($canPublish)
                            <form method="POST" action="{{ route('backend.event.publish', $event) }}">
                                @csrf
                                <button class="btn btn-primary btn-xs">Xuất bản</button>
                            </form>
                            @endif
                            @endcan
                            @can('archive', $event)
                            @if($canArchive)
                            <form method="POST" action="{{ route('backend.event.archive', $event) }}"
                                  onsubmit="return confirm('Lưu trữ sự kiện &quot;{{ $event->title }}&quot;? Sự kiện sẽ không còn hiển thị công khai.')">
                                @csrf
                                <button class="btn btn-ghost btn-xs">Lưu trữ</button>
                            </form>
                            @endif
                            @endcan
                            @can('update', $event)
                            <a href="{{ route('backend.event.edit', $event) }}" class="btn btn-ghost btn-xs btn-square" title="Sửa">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-10 text-base-content/40">
                        Chưa có sự kiện nào.
                        <p class="text-xs mt-1">Bấm "Thêm sự kiện" để tự nhập, hoặc chờ độc giả nộp qua cổng thông tin.</p>
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($events->hasPages())
    <div class="mt-4">{{ $events->onEachSide(1)->links() }}</div>
    @endif

    {{-- ── Modal từ chối — dùng chung 1 modal cho mọi hàng, đổi action/tiêu đề qua Alpine ── --}}
    <div class="modal" :class="rejectOpen ? 'modal-open' : ''" x-cloak>
        <div class="modal-box">
            <h3 class="font-bold text-lg">Từ chối sự kiện</h3>
            <p class="text-sm text-base-content/60 mt-1" x-text="'\"' + rejectTitle + '\"'"></p>
            <form method="POST" :action="rejectAction" class="mt-4">
                @csrf
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Lý do từ chối <span class="text-error">*</span></span></label>
                    <textarea name="rejected_reason" rows="3" required maxlength="255"
                              class="textarea textarea-bordered textarea-sm w-full"
                              placeholder="VD: Thiếu thông tin địa điểm, thông tin chưa chính xác..."></textarea>
                    <p class="text-xs text-base-content/40 mt-1">Lý do này sẽ được gửi qua email cho người nộp.</p>
                </div>
                <div class="modal-action">
                    <button type="button" class="btn btn-ghost btn-sm" @click="rejectOpen = false">Huỷ</button>
                    <button type="submit" class="btn btn-error btn-sm">Từ chối sự kiện</button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" @click="rejectOpen = false"></div>
    </div>
</div>
@endsection
