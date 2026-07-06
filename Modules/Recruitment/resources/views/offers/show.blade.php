@extends('layouts.backend')

@section('title', 'Offer Letter')


@section('content')
<div x-data="rcOfferShow" class="p-6 max-w-3xl space-y-5">

    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-xl font-bold">Offer Letter</h1>
            <p class="text-sm opacity-60 mt-0.5">
                Ứng viên: <strong>{{ $offer->application?->candidate?->full_name }}</strong>
            </p>
            @php $badge = $offer->status?->badgeClass() ?? 'badge-ghost'; @endphp
            <span class="badge {{ $badge }} badge-sm mt-2">{{ $offer->status?->label() }}</span>
        </div>

        @can('update', $offer->application)
        @if(!$offer->status?->isTerminal())
        <div class="flex flex-wrap gap-2">
            @if($offer->status?->value === 'draft')
            <button @click="action('submit-for-approval', 'Gửi yêu cầu duyệt?')" class="btn btn-warning btn-sm">Gửi duyệt</button>
            <button @click="action('send', 'Gửi thẳng cho ứng viên?')" class="btn btn-info btn-sm">Gửi ngay</button>
            @endif
            @if($offer->status?->value === 'pending_approval')
            <button @click="action('approve', 'Duyệt offer này?')" class="btn btn-success btn-sm">Duyệt</button>
            @endif
            @if($offer->status?->value === 'approved')
            <button @click="action('send', 'Gửi offer đến ứng viên?')" class="btn btn-primary btn-sm">Gửi ứng viên</button>
            @endif
            @if($offer->status?->value === 'sent')
            <button @click="action('accept', 'Xác nhận ứng viên chấp nhận offer?')" class="btn btn-success btn-sm">Chấp nhận</button>
            <button @click="rejectOffer()" class="btn btn-error btn-outline btn-sm">Từ chối</button>
            @endif
            <button @click="action('revoke', 'Thu hồi offer này?')" class="btn btn-ghost btn-sm text-error">Thu hồi</button>
        </div>
        @endif
        @endcan
    </div>

    {{-- Offer details --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5">
            <div class="grid grid-cols-2 gap-x-8 gap-y-3">

                <div class="flex justify-between text-sm border-b border-base-200 pb-2">
                    <span class="opacity-60">Lương đề xuất</span>
                    <span class="font-bold text-lg">{{ number_format($offer->salary_offered) }} {{ $offer->currency }}</span>
                </div>

                <div class="flex justify-between text-sm border-b border-base-200 pb-2">
                    <span class="opacity-60">Ngày bắt đầu</span>
                    <span class="font-medium">{{ $offer->start_date?->format('d/m/Y') }}</span>
                </div>

                <div class="flex justify-between text-sm border-b border-base-200 pb-2">
                    <span class="opacity-60">Thử việc</span>
                    <span>{{ $offer->probation_days }} ngày</span>
                </div>

                @if($offer->expire_at)
                <div class="flex justify-between text-sm border-b border-base-200 pb-2">
                    <span class="opacity-60">Hạn trả lời</span>
                    <span class="{{ $offer->expire_at->isPast() ? 'text-error' : '' }}">
                        {{ $offer->expire_at?->format('d/m/Y') }}
                    </span>
                </div>
                @endif

                @if($offer->approved_by)
                <div class="flex justify-between text-sm border-b border-base-200 pb-2">
                    <span class="opacity-60">Duyệt bởi</span>
                    <span>{{ $offer->approvedBy?->name }} — {{ $offer->approved_at?->format('d/m/Y H:i') }}</span>
                </div>
                @endif

                @if($offer->sent_at)
                <div class="flex justify-between text-sm border-b border-base-200 pb-2">
                    <span class="opacity-60">Đã gửi lúc</span>
                    <span>{{ $offer->sent_at?->format('d/m/Y H:i') }}</span>
                </div>
                @endif

                @if($offer->responded_at)
                <div class="flex justify-between text-sm border-b border-base-200 pb-2">
                    <span class="opacity-60">Phản hồi lúc</span>
                    <span>{{ $offer->responded_at?->format('d/m/Y H:i') }}</span>
                </div>
                @endif

                <div class="flex justify-between text-sm border-b border-base-200 pb-2">
                    <span class="opacity-60">Tạo bởi</span>
                    <span>{{ $offer->createdBy?->name }} — {{ $offer->created_at?->format('d/m/Y') }}</span>
                </div>

            </div>

            @if($offer->benefits_note)
            <div class="mt-4 pt-4 border-t border-base-200">
                <p class="text-sm font-medium opacity-60 mb-1">Phúc lợi</p>
                <p class="text-sm whitespace-pre-wrap">{{ $offer->benefits_note }}</p>
            </div>
            @endif

            @if($offer->rejection_reason)
            <div class="mt-4 pt-4 border-t border-base-200">
                <p class="text-sm font-medium text-error mb-1">Lý do từ chối</p>
                <p class="text-sm">{{ $offer->rejection_reason }}</p>
            </div>
            @endif
        </div>
    </div>

    <a href="{{ route('backend.recruitment.applications.show', $offer->application) }}" class="btn btn-ghost btn-sm">
        ← Quay lại đơn ứng tuyển
    </a>
</div>

{{-- Reject modal --}}
<dialog id="reject-offer-modal" class="modal">
    <div class="modal-box max-w-md">
        <h3 class="font-bold text-lg mb-4 text-error">Từ chối offer</h3>
        <div class="form-control mb-5">
            <label class="label"><span class="label-text">Lý do từ chối</span></label>
            <textarea x-model="rejectReason" class="textarea textarea-bordered" rows="3"
                      placeholder="Ứng viên không chấp nhận mức lương..."></textarea>
        </div>
        <div class="modal-action">
            <button class="btn btn-ghost" onclick="document.getElementById('reject-offer-modal').close()">Hủy</button>
            <button class="btn btn-error" @click="confirmReject()">Từ chối</button>
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    var CSRF = '{{ csrf_token() }}';
    var BASE = '{{ url('dashboard/recruitment/offers/' . $offer->id) }}';

    Alpine.data('rcOfferShow', function() {
        return {
            rejectReason: '',

            action: function(endpoint, confirmMsg) {
                if (!confirm(confirmMsg)) return;
                fetch(BASE + '/' + endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({}),
                })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.warning) { alert(d.message); return; }
                    window.location.reload();
                })
                .catch(function(e) { console.error(e); alert('Lỗi xảy ra'); });
            },

            rejectOffer: function() {
                this.rejectReason = '';
                document.getElementById('reject-offer-modal').showModal();
            },

            confirmReject: function() {
                var self = this;
                fetch(BASE + '/reject', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ reason: self.rejectReason }),
                })
                .then(function(r) { return r.json(); })
                .then(function() {
                    document.getElementById('reject-offer-modal').close();
                    window.location.reload();
                })
                .catch(function(e) { console.error(e); alert('Lỗi'); });
            },
        };
    });
});
</script>
@endpush
