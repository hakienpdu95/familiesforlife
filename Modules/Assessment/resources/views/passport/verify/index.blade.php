@extends('layouts.backend')
@section('title', 'Xác minh danh tính — Competency Passport')

@section('content')

{{-- ── Breadcrumb ──────────────────────────────────────────────────────────── --}}
<div class="flex items-center gap-2 text-sm text-base-content/50 mb-4">
    <a href="{{ route('passport.index') }}" class="hover:text-primary">Competency Passport</a>
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-base-content">Xác minh danh tính</span>
</div>

{{-- ── Alerts ──────────────────────────────────────────────────────────────── --}}
@if(session('success'))
<div class="alert alert-success mb-4">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span>{{ session('success') }}</span>
</div>
@endif
@if(session('info'))
<div class="alert alert-info mb-4">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span>{{ session('info') }}</span>
</div>
@endif
@if(session('error'))
<div class="alert alert-error mb-4">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span>{{ session('error') }}</span>
</div>
@endif
@if(session('status') === 'verification-link-sent')
<div class="alert alert-info mb-4">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
    <div>
        <p class="font-semibold">Email xác minh đã được gửi!</p>
        <p class="text-sm">Kiểm tra hộp thư của <strong>{{ $user->email }}</strong> và nhấp vào link xác minh. Link có hiệu lực trong 60 phút.</p>
    </div>
</div>
@endif

{{-- ── Current trust level + progress ─────────────────────────────────────── --}}
<div class="card bg-base-100 border border-base-200 shadow-sm mb-6">
    <div class="card-body">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold">Xác minh danh tính</h1>
                <p class="text-sm text-base-content/50 mt-0.5">Mỗi cấp độ xác minh mở thêm tính năng mới trên nền tảng</p>
            </div>
            <div>
                @if($user->hasVerifiedEmail())
                    <div class="badge badge-outline badge-lg gap-1.5 px-4">✉ Email xác minh — Lv1</div>
                @else
                    <div class="badge badge-ghost badge-lg px-4">Chưa xác minh — Lv0</div>
                @endif
            </div>
        </div>

        {{-- Progress steps --}}
        <div class="mt-5">
            <ul class="steps steps-horizontal w-full text-xs">
                <li class="step step-primary">Đăng ký</li>
                <li class="step {{ $user->hasVerifiedEmail() ? 'step-primary' : '' }}">✉ Email</li>
            </ul>
        </div>
    </div>
</div>

{{-- ── Tính năng được mở khoá theo cấp độ ─────────────────────────────────── --}}
<div class="grid grid-cols-1 gap-3 mb-6">

    {{-- Lv1 --}}
    <div class="rounded-xl border p-4 {{ $user->hasVerifiedEmail() ? 'border-success/40 bg-success/5' : 'border-base-200 bg-base-100 opacity-60' }}">
        <div class="flex items-center gap-2 mb-3">
            <span class="text-lg">✉</span>
            <span class="font-semibold text-sm">Lv1 — Email</span>
            @if($user->hasVerifiedEmail())
            <svg class="w-4 h-4 text-success ml-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            @endif
        </div>
        <ul class="space-y-1.5 text-xs text-base-content/70">
            <li class="flex gap-1.5"><svg class="w-3.5 h-3.5 text-success shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>Truy cập toàn bộ hệ thống</li>
            <li class="flex gap-1.5"><svg class="w-3.5 h-3.5 text-success shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>Xem Career Journal &amp; Passport</li>
            <li class="flex gap-1.5"><svg class="w-3.5 h-3.5 text-success shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>Làm khảo sát TDWCF &amp; Sandbox</li>
            <li class="flex gap-1.5"><svg class="w-3.5 h-3.5 text-success shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>Nhận chứng nhận AI nội bộ</li>
        </ul>
    </div>

</div>

{{-- ── Verification cards ────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 max-w-xl gap-5">

{{-- ── Lv1: Email ─────────────────────────────────────────────────────────── --}}
<div class="card bg-base-100 border {{ $user->hasVerifiedEmail() ? 'border-success/30' : 'border-base-200' }} shadow-sm">
    <div class="card-body">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full {{ $user->hasVerifiedEmail() ? 'bg-success/10 text-success' : 'bg-base-200 text-base-content/40' }} flex items-center justify-center text-xl shrink-0">✉</div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <h3 class="font-semibold">Email xác minh</h3>
                    @if($user->hasVerifiedEmail())
                        <span class="badge badge-success badge-sm">Hoàn thành</span>
                    @else
                        <span class="badge badge-ghost badge-sm">Lv1</span>
                    @endif
                </div>
                <p class="text-sm text-base-content/60 mb-3">Xác nhận địa chỉ email để truy cập toàn bộ hệ thống, Career Journal và Sandbox.</p>

                @if($user->hasVerifiedEmail())
                    {{-- Already verified --}}
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-success shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm text-success font-medium">{{ $user->email }}</p>
                    </div>
                @else
                    {{-- Not verified — delegate to native Fortify --}}
                    <div class="text-sm text-base-content/60 mb-3">
                        <span class="font-mono">{{ $user->email }}</span> — chưa xác minh
                    </div>
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Gửi email xác minh
                        </button>
                        <p class="text-xs text-base-content/40 mt-1.5">Tối đa 6 lần/phút. Link có hiệu lực 60 phút.</p>
                    </form>
                @endif

            </div>
        </div>
    </div>
</div>

</div>

{{-- ── Verification history ─────────────────────────────────────────────────── --}}
@if($verifications->count())
<div class="mt-6">
    <h2 class="text-sm font-semibold text-base-content/60 mb-3 uppercase tracking-wide">Lịch sử xác minh</h2>
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Phương thức</th>
                        <th>Trạng thái</th>
                        <th>Ngày xác minh</th>
                        <th>Hết hạn</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($verifications as $v)
                    <tr>
                        <td><span class="font-medium">{{ $v->method->label() }}</span></td>
                        <td>
                            @if($v->status->value === 'verified')
                                <span class="badge badge-success badge-sm">Đã xác minh</span>
                            @elseif($v->status->value === 'expired')
                                <span class="badge badge-warning badge-sm">Hết hạn</span>
                            @else
                                <span class="badge badge-ghost badge-sm">{{ $v->status->label() }}</span>
                            @endif
                        </td>
                        <td class="text-sm">{{ $v->verified_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="text-sm text-base-content/50">{{ $v->expires_at?->format('d/m/Y') ?? 'Không giới hạn' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection
