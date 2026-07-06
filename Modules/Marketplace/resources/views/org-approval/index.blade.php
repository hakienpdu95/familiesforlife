@extends('layouts.backend')

@section('title', 'Duyệt tổ chức — Marketplace')


@section('content')
<div class="px-6 py-4 space-y-4">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">Duyệt tổ chức đăng ký</h1>
            <p class="text-sm opacity-60 mt-0.5">Các doanh nghiệp đã đăng ký qua Marketplace, đang chờ phê duyệt</p>
        </div>
        <div class="badge badge-warning badge-lg">{{ $pending->total() }} chờ duyệt</div>
    </div>

    @if($pending->isEmpty())
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body text-center py-12">
            <svg class="w-12 h-12 mx-auto opacity-30 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-lg font-semibold opacity-50">Không có tổ chức nào chờ duyệt</p>
        </div>
    </div>
    @else
    <div class="space-y-4">
        @foreach($pending as $listing)
        @php $org = $listing->organization; $hrUser = $org?->users?->first(); @endphp
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body">
                <div class="flex items-start justify-between gap-4">

                    <div class="flex-1 min-w-0">
                        {{-- Org info --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-semibold text-base">{{ $org?->name ?? '—' }}</h3>
                            <span class="badge badge-warning badge-xs">Chờ duyệt</span>
                        </div>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-sm opacity-60">
                            @if($org?->email)
                            <span>{{ $org->email }}</span>
                            @endif
                            @if($org?->website)
                            <a href="{{ $org->website }}" target="_blank" class="hover:text-primary">{{ $org->website }}</a>
                            @endif
                            <span>Đăng ký: {{ $listing->created_at?->format('d/m/Y H:i') }}</span>
                        </div>

                        {{-- Listing preview --}}
                        <div class="mt-3 p-3 bg-base-200 rounded-lg">
                            <p class="text-xs uppercase tracking-wide opacity-50 mb-1">Tin đăng</p>
                            <p class="font-medium text-sm">{{ $listing->title }}</p>
                            @if($listing->location)
                            <p class="text-xs opacity-60">{{ $listing->location }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-col gap-2 shrink-0">
                        <form action="{{ route('backend.marketplace.org-approvals.approve', $org) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="btn btn-success btn-sm w-full"
                                    onclick="return confirm('Duyệt tổ chức &quot;{{ addslashes($org?->name) }}&quot;? Tất cả tin đang chờ sẽ được kích hoạt.')">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Duyệt
                            </button>
                        </form>

                        <button class="btn btn-error btn-outline btn-sm"
                                onclick="document.getElementById('reject-modal-{{ $org?->id }}').showModal()">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Từ chối
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reject modal --}}
        <dialog id="reject-modal-{{ $org?->id }}" class="modal">
            <div class="modal-box">
                <h3 class="font-bold text-lg">Từ chối tổ chức</h3>
                <p class="py-2 text-sm opacity-70">Bạn đang từ chối <strong>{{ $org?->name }}</strong>. Lý do sẽ được ghi nhận.</p>
                <form action="{{ route('backend.marketplace.org-approvals.reject', $org) }}" method="POST">
                    @csrf
                    <div class="form-control mt-3">
                        <label class="label"><span class="label-text">Lý do từ chối</span></label>
                        <textarea name="reason" rows="3" placeholder="Không đáp ứng điều kiện..." class="textarea textarea-bordered"></textarea>
                    </div>
                    <div class="modal-action">
                        <form method="dialog"><button class="btn btn-ghost btn-sm">Hủy</button></form>
                        <button type="submit" class="btn btn-error btn-sm">Xác nhận từ chối</button>
                    </div>
                </form>
            </div>
        </dialog>
        @endforeach

        <div class="mt-4">{{ $pending->links() }}</div>
    </div>
    @endif

</div>
@endsection
