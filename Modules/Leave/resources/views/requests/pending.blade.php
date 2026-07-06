@extends('layouts.backend')
@section('title', 'Đơn chờ duyệt')


@section('content')
<div>
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold">Đơn chờ tôi duyệt</h1>
            <p class="text-sm text-base-content/50 mt-0.5">{{ $requests->count() }} đơn đang chờ xử lý</p>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
    <div class="alert alert-error mb-4">
        <ul class="text-sm list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    @forelse($requests as $req)
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-3">
        <div class="card-body py-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="font-semibold">{{ $req->employee?->full_name }}
                        <span class="text-base-content/50 font-normal text-sm">({{ $req->employee?->employee_code }})</span>
                    </div>
                    <div class="text-sm text-base-content/60 mt-0.5">
                        {{ $req->employee?->department?->name }} ·
                        <span class="badge badge-ghost badge-sm">{{ $req->leave_type->label() }}</span>
                    </div>
                    <div class="text-sm mt-2">
                        {{ $req->date_from->format('d/m/Y') }} → {{ $req->date_to->format('d/m/Y') }}
                        · <strong>{{ $req->days_count }} ngày</strong>
                    </div>
                    @if($req->reason)
                    <div class="text-sm text-base-content/60 mt-1 italic">"{{ $req->reason }}"</div>
                    @endif
                </div>

                <div class="flex gap-2">
                    <form method="POST" action="{{ route('backend.leave.requests.approve', $req) }}">
                        @csrf
                        <button class="btn btn-success btn-sm">Duyệt</button>
                    </form>

                    <div x-data="{ open: false }">
                        <button @click="open = true" class="btn btn-error btn-sm">Từ chối</button>
                        <div x-show="open" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center" x-cloak>
                            <div class="card bg-base-100 w-96 shadow-xl">
                                <div class="card-body">
                                    <h3 class="card-title text-base">Lý do từ chối</h3>
                                    <form method="POST" action="{{ route('backend.leave.requests.reject', $req) }}">
                                        @csrf
                                        <textarea name="rejected_reason" rows="3" class="textarea textarea-bordered w-full mb-3"
                                                  placeholder="Nhập lý do từ chối..." required></textarea>
                                        <div class="flex gap-2 justify-end">
                                            <button type="button" @click="open = false" class="btn btn-ghost btn-sm">Hủy</button>
                                            <button type="submit" class="btn btn-error btn-sm">Xác nhận</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('backend.leave.requests.show', $req) }}" class="btn btn-ghost btn-sm">Chi tiết</a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="card bg-base-100 border border-base-200">
        <div class="card-body text-center py-12 text-base-content/40">
            Không có đơn nào chờ duyệt.
        </div>
    </div>
    @endforelse
</div>
@endsection
