@extends('layouts.backend')
@section('title', 'Sửa tin — ' . $listing->title)

@section('content')
@php
    $status = $listing->approvalStatus();
@endphp
<div class="max-w-3xl">

    <div class="flex items-center gap-2 text-sm text-base-content/50 mb-6">
        <a href="{{ route('backend.real-estate.index') }}" class="hover:text-primary">Bất động sản</a>
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span>Sửa tin</span>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold text-base-content truncate">{{ $listing->title }}</h1>
        @if($status)
        <span class="badge {{ $status->badgeClass() }}">{{ $status->label() }}</span>
        @endif
    </div>

    @foreach(['success','error'] as $type)
        @if(session($type))
        <div class="alert alert-{{ $type }} mb-4 text-sm"><span>{{ session($type) }}</span></div>
        @endif
    @endforeach

    {{-- ── Workflow duyệt (§5.5/§6 spec Bán) — Modules\Approval dùng chung ──────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm mb-6">
        <div class="card-body flex-row flex-wrap items-center gap-2">
            @can('submitForApproval', $listing)
            <form method="POST" action="{{ route('backend.real-estate.submit-approval', $listing) }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">Gửi duyệt</button>
            </form>
            @endcan
            @can('approve', $listing)
            <form method="POST" action="{{ route('backend.real-estate.approve-content', $listing) }}">
                @csrf
                <button type="submit" class="btn btn-info btn-sm">Duyệt nội dung</button>
            </form>
            @endcan
            @can('publishApproval', $listing)
            <form method="POST" action="{{ route('backend.real-estate.publish-content', $listing) }}">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">Xuất bản</button>
            </form>
            @endcan
            @can('reject', $listing)
            <button type="button" class="btn btn-error btn-outline btn-sm" onclick="rejectRealEstateModal.showModal()">Từ chối</button>
            @endcan
            @can('archiveApproval', $listing)
            <form method="POST" action="{{ route('backend.real-estate.archive-content', $listing) }}" onsubmit="return confirm('Lưu trữ tin này?')">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm">Lưu trữ</button>
            </form>
            @endcan
        </div>
    </div>

    <form method="POST" action="{{ route('backend.real-estate.update', $listing) }}">
        @csrf
        @method('PUT')
        @include('realestate::admin.listings._form', ['listing' => $listing])

        <div class="flex justify-between items-center gap-2 mt-6">
            @can('delete', $listing)
            <form method="POST" action="{{ route('backend.real-estate.destroy', $listing) }}" onsubmit="return confirm('Xoá tin này?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-error btn-outline">Xoá tin</button>
            </form>
            @endcan
            <div class="flex gap-2 ml-auto">
                <a href="{{ route('backend.real-estate.index') }}" class="btn btn-ghost">Hủy</a>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </div>
    </form>

</div>

@can('reject', $listing)
<dialog id="rejectRealEstateModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Từ chối duyệt</h3>
        <form method="POST" action="{{ route('backend.real-estate.reject-content', $listing) }}" class="mt-3">
            @csrf
            <textarea name="reason" rows="3" minlength="10" required class="textarea textarea-bordered w-full" placeholder="Lý do từ chối (tối thiểu 10 ký tự)"></textarea>
            <div class="modal-action mt-4">
                <button type="submit" class="btn btn-error btn-sm">Từ chối</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="rejectRealEstateModal.close()">Hủy</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endcan
@endsection
